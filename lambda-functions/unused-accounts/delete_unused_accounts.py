"""
Delete unused accounts - accounts that have not been logged into for N months (default 6), or never logged into,
and have no associated LPAs.

  1. scripts/unused_accounts/dynamodb_export.py exports DynamoDB to S3 and loads it into Athena.
  2. scripts/unused_accounts/discover_unused_accounts.py runs the Athena query and writes work files to
     s3://<bucket>/<work prefix>/unused-accounts-<n>.json
  3. This lambda, reads the work files, re-checks every account against DynamoDB (the export
     may be out of date) and writes a deletion plan per account to s3://<bucket>/<plan prefix>/ for review.
     Each completed work file is moved to done/<work prefix>/ (suffixed with the processing time) and the lambda
     stops before it times out, so it can be rerun until no work files remain.
  4. This lambda, with Execute=true, re-checks every plan against current data and, if nothing has changed,
     deletes the account. Plans are then moved to done/ (deleted) or skipped/ (no longer eligible).

ActorUsers may also hold "IDENTITY#<identity>" pinning rows, written by the API (service-api ActorUsers::add) to stop a
second account being created for the same OneLogin identity. When an account is deleted, its pinning row is deleted
in the same transaction (as ActorUsers::delete does), unless another account still has the same identity, in which
case the pin is left in place for that account.
"""

import calendar
import hashlib
import json
import logging
import os
import threading
import time
from concurrent.futures import FIRST_COMPLETED, ThreadPoolExecutor, wait
from datetime import UTC, date, datetime

import boto3
from boto3.dynamodb.conditions import Key
from botocore.exceptions import ClientError

logger = logging.getLogger()
logger.setLevel("INFO")

WORK_FILE_NAME = "unused-accounts"
DONE_PREFIX = "done"
SKIPPED_PREFIX = "skipped"
IDENTITY_PIN_PREFIX = "IDENTITY#"

# Stop starting new work when the lambda has less than this much time left
PLAN_TIME_RESERVE_MS = 120_000
EXECUTE_TIME_RESERVE_MS = 15_000

# Accounts in a work file are checked concurrently
PLAN_THREADS = 10
EXECUTE_THREADS = PLAN_THREADS

# Create connections to AWS services
dynamodb_options = {}
dynamodb_client = None
s3 = None
thread_local = threading.local()


def initialise():
    global dynamodb_options, dynamodb_client, s3, thread_local

    dynamodb_options = {"region_name": "eu-west-1"}
    if "AWS_ENDPOINT_DYNAMODB" in os.environ:
        dynamodb_options["endpoint_url"] = os.environ["AWS_ENDPOINT_DYNAMODB"]

    dynamodb_client = boto3.client("dynamodb", **dynamodb_options)
    thread_local = threading.local()

    if "AWS_ENDPOINT_S3" in os.environ:
        s3 = boto3.client(
            "s3",
            region_name="eu-west-1",
            endpoint_url=os.environ["AWS_ENDPOINT_S3"],
        )
    else:
        s3 = boto3.client("s3", region_name="eu-west-1")


# ---------------------------------------------------------------------------------------------------------------------
# Tables
# ---------------------------------------------------------------------------------------------------------------------

def get_table_name(prefix, name):
    return f"{prefix}-{name}" if prefix else name


def get_dynamodb():
    """boto3 resources are not thread safe, so each thread gets its own from its own session."""
    if not hasattr(thread_local, "dynamodb"):
        thread_local.dynamodb = boto3.session.Session().resource("dynamodb", **dynamodb_options)
    return thread_local.dynamodb


def get_actor_users_table(prefix=None):
    return get_dynamodb().Table(get_table_name(prefix, "ActorUsers"))


def get_lpa_table(prefix=None):
    return get_dynamodb().Table(get_table_name(prefix, "UserLpaActorMap"))


def get_actor(table_prefix, user_id):
    response = get_actor_users_table(table_prefix).get_item(Key={"Id": user_id}, ConsistentRead=True)
    return response.get("Item")


def get_user_lpa_count(table_prefix, user_id):
    """Count the user's rows in UserLpaActorMap via the UserIndex GSI."""
    lpa_table = get_lpa_table(table_prefix)
    count = 0
    kwargs = {
        "IndexName": "UserIndex",
        "KeyConditionExpression": Key("UserId").eq(user_id),
        "Select": "COUNT",
    }

    while True:
        response = lpa_table.query(**kwargs)
        count += response.get("Count", 0)
        if "LastEvaluatedKey" not in response:
            return count
        kwargs["ExclusiveStartKey"] = response["LastEvaluatedKey"]


def is_identity_pin(user_id):
    return user_id.startswith(IDENTITY_PIN_PREFIX)


def other_accounts_with_identity(table_prefix, identity, user_id):
    """Ids of other accounts with the same identity, via the IdentityIndex GSI (pinning rows have no Identity)."""
    table = get_actor_users_table(table_prefix)
    others = []
    kwargs = {
        "IndexName": "IdentityIndex",
        "KeyConditionExpression": Key("Identity").eq(identity),
        "ProjectionExpression": "Id",
    }

    while True:
        response = table.query(**kwargs)
        others.extend(item["Id"] for item in response.get("Items", []) if item["Id"] != user_id)
        if "LastEvaluatedKey" not in response:
            return sorted(others)
        kwargs["ExclusiveStartKey"] = response["LastEvaluatedKey"]


def identity_pin_to_delete(table_prefix, actor):
    """The account's IDENTITY# pinning row id, or None if it has no identity or the identity is shared."""
    identity = actor.get("Identity")
    if not identity:
        return None
    if other_accounts_with_identity(table_prefix, identity, actor["Id"]):
        return None
    return f"{IDENTITY_PIN_PREFIX}{identity}"


# ---------------------------------------------------------------------------------------------------------------------
# Eligibility - must match the Athena query in scripts/unused_accounts/discover_unused_accounts.py
# ---------------------------------------------------------------------------------------------------------------------

def subtract_months(day, months):
    """Calendar month subtraction (same as Athena's date_add('month', -n, current_date))."""
    month_index = day.year * 12 + (day.month - 1) - months
    year, month = divmod(month_index, 12)
    month += 1
    return date(year, month, min(day.day, calendar.monthrange(year, month)[1]))


def inactivity_threshold(months_inactive, today=None):
    """Accounts whose last login date is on or before this ISO date are inactive."""
    today = today or datetime.now(UTC).date()
    return subtract_months(today, months_inactive).isoformat()


def is_inactive(last_login, threshold):
    """No login recorded, or last login (date part of an ISO 8601 string) on or before the threshold date."""
    if not last_login:
        return True
    return last_login[:10] <= threshold


def assess_account(table_prefix, user_id, months_inactive, threshold):
    """
    Build a deletion plan for an account from current DynamoDB data.

    Returns (plan, None) if the account should be deleted, otherwise (None, reason).
    """
    if is_identity_pin(user_id):
        return None, "identity pinning record, not an account"

    actor = get_actor(table_prefix, user_id)
    if actor is None:
        return None, "account no longer exists"

    last_login = actor.get("LastLogin") or None
    if not is_inactive(last_login, threshold):
        return None, f"logged in since the work file was created (LastLogin {last_login})"

    lpa_count = get_user_lpa_count(table_prefix, user_id)
    if lpa_count > 0:
        return None, f"account has {lpa_count} LPA(s)"

    if last_login:
        reason = f"No login for {months_inactive}+ months and no associated LPAs"
    else:
        reason = "Never logged in and no associated LPAs"

    return {
        "account_id": actor["Id"],
        "identity": actor.get("Identity"),
        "email": actor.get("Email"),
        "last_login": last_login,
        "lpa_count": lpa_count,
        "identity_pin": identity_pin_to_delete(table_prefix, actor),
        "reason": reason,
    }, None


# ---------------------------------------------------------------------------------------------------------------------
# S3 helpers
# ---------------------------------------------------------------------------------------------------------------------

def list_keys(bucket, prefix):
    paginator = s3.get_paginator("list_objects_v2")
    for page in paginator.paginate(Bucket=bucket, Prefix=prefix):
        for obj in page.get("Contents", []):
            yield obj["Key"]


def load_from_s3(bucket, key):
    response = s3.get_object(Bucket=bucket, Key=key)
    return json.loads(response["Body"].read())


def move_object(bucket, key, destination_key):
    s3.copy_object(Bucket=bucket, CopySource=f"{bucket}/{key}", Key=destination_key)
    s3.delete_object(Bucket=bucket, Key=key)


def out_of_time(context, reserve_ms):
    return context is not None and context.get_remaining_time_in_millis() < reserve_ms


def get_deletion_plan_key(plan_prefix, account_id):
    filename = hashlib.md5(account_id.encode()).hexdigest()
    return f"{plan_prefix}/{filename}.json"


def save_deletion_plan(bucket, plan_prefix, plan):
    key = get_deletion_plan_key(plan_prefix, plan["account_id"])
    s3.put_object(
        Bucket=bucket,
        Key=key,
        Body=json.dumps(plan, indent=2),
        ContentType="application/json",
        Metadata={"account-id": plan["account_id"]},
    )
    return key


def work_file_number(key):
    try:
        return int(key.rsplit("-", 1)[-1].removesuffix(".json"))
    except ValueError:
        return 0


def get_done_work_file_key(work_file_key):
    """done/<work_file_key> with the processing time added, so earlier runs' files are never overwritten."""
    processed_at = datetime.now(UTC).strftime("%Y%m%dT%H%M%S%fZ")
    return f"{DONE_PREFIX}/{work_file_key.removesuffix('.json')}-{processed_at}.json"


# ---------------------------------------------------------------------------------------------------------------------
# Plan
# ---------------------------------------------------------------------------------------------------------------------

def build_deletion_plans(table_prefix, bucket, work_prefix, plan_prefix, months_inactive, context=None):
    """
    Re-check every account in the work files and write a deletion plan for each one still eligible.

    Work files are processed in number order until the lambda is close to timing out, with the accounts in each file
    checked concurrently. Each fully processed work file is moved to done/ so that rerunning the lambda carries on
    with the remaining files.
    """
    script_start = time.perf_counter()
    threshold = inactivity_threshold(months_inactive)

    def plan_work_item(item):
        """Returns (user_id, skip reason), with no skip reason if a plan was saved."""
        user_id = item.get("user_id")
        if not user_id:
            return None, "work item has no user_id"

        plan, skip_reason = assess_account(table_prefix, user_id, months_inactive, threshold)
        if plan is not None:
            save_deletion_plan(bucket, plan_prefix, plan)
        return user_id, skip_reason

    logger.info(f"Processing work files (inactive = last login on or before {threshold}, or never)...")
    work_files = sorted(list_keys(bucket, f"{work_prefix}/{WORK_FILE_NAME}-"), key=work_file_number)
    if not work_files:
        logger.info(f" - No work files found in s3://{bucket}/{work_prefix}/")

    work_items = 0
    planned = 0
    skipped = 0
    files_processed = 0
    files_failed = 0
    stopped_reason = "complete"

    with ThreadPoolExecutor(max_workers=PLAN_THREADS) as executor:
        for key in work_files:
            if out_of_time(context, PLAN_TIME_RESERVE_MS):
                stopped_reason = "time"
                break

            try:
                # executor.map re-raises the first worker exception here, failing the whole file
                for user_id, skip_reason in executor.map(plan_work_item, load_from_s3(bucket, key)):
                    work_items += 1
                    if skip_reason is None:
                        planned += 1
                    else:
                        logger.info(f" - Skipping {user_id}: {skip_reason}")
                        skipped += 1

                move_object(bucket, key, get_done_work_file_key(key))
                files_processed += 1
            except (ClientError, ValueError) as e:
                # leave the work file where it is so it can be retried
                logger.error(f" - ERR work file {key} could not be processed: {e}")
                files_failed += 1

    summary = {
        "work_files": len(work_files),
        "files_processed": files_processed,
        "files_failed": files_failed,
        "files_remaining": len(work_files) - files_processed - files_failed,
        "stopped_reason": stopped_reason,
        "work_items": work_items,
        "plans_saved": planned,
        "skipped": skipped,
        "duration_seconds": round(time.perf_counter() - script_start, 3),
    }
    logger.info(f"DELETION PLAN SUMMARY: {json.dumps(summary)}")
    return summary


# ---------------------------------------------------------------------------------------------------------------------
# Execute
# ---------------------------------------------------------------------------------------------------------------------

def execute_deletion_plan(table_prefix, plan, months_inactive, threshold):
    """
    Delete the account in the plan if it is still eligible and unchanged.

    Returns ("deleted", None) or ("skipped", reason).
    """
    account_id = plan["account_id"]

    # generate a plan based on current data - it must match the stored plan
    current_plan, skip_reason = assess_account(table_prefix, account_id, months_inactive, threshold)
    if current_plan is None:
        return "skipped", skip_reason
    if current_plan != plan:
        return "skipped", "account has changed since the plan was made"

    # Only delete if LastLogin is still what we checked, in case the user logs in right now
    if plan["last_login"]:
        condition = "attribute_exists(Id) AND LastLogin = :last_login"
        values = {":last_login": {"S": plan["last_login"]}}
    else:
        condition = "attribute_exists(Id) AND attribute_not_exists(LastLogin)"
        values = None

    delete = {
        "TableName": get_table_name(table_prefix, "ActorUsers"),
        "Key": {"Id": {"S": account_id}},
        "ConditionExpression": condition,
    }
    if values:
        delete["ExpressionAttributeValues"] = values

    transact_items = [{"Delete": delete}]

    # remove the identity pin with the account (as the API's ActorUsers::delete does), so no orphan is left behind
    if plan.get("identity_pin"):
        transact_items.append({
            "Delete": {
                "TableName": get_table_name(table_prefix, "ActorUsers"),
                "Key": {"Id": {"S": plan["identity_pin"]}},
            }
        })

    try:
        dynamodb_client.transact_write_items(TransactItems=transact_items)
    except ClientError as e:
        if e.response.get("Error", {}).get("Code") == "TransactionCanceledException":
            return "skipped", "account changed during execution"
        raise

    return "deleted", None


def execute_all_deletion_plans(bucket, plan_prefix, table_prefix, months_inactive, context=None):
    """Execute every plan in s3://<bucket>/<plan_prefix>/, until the lambda is close to timing out."""
    script_start = time.perf_counter()
    threshold = inactivity_threshold(months_inactive)

    logger.info(f"Executing all deletion plans in \"{bucket}/{plan_prefix}/\"")
    deleted = 0
    skipped = 0
    failed = 0
    stopped_reason = "complete"

    # list up front so moving plans to done/ or skipped/ doesn't affect pagination
    plan_keys = list(list_keys(bucket, f"{plan_prefix}/"))

    def execute_plan(plan_key):
        try:
            plan = load_from_s3(bucket, plan_key)
            result, reason = execute_deletion_plan(table_prefix, plan, months_inactive, threshold)

            if result == "deleted":
                move_object(bucket, plan_key, f"{DONE_PREFIX}/{plan_key}")
                return "deleted"
            else:
                logger.info(f" - Skipping {plan.get('account_id')}: {reason}")
                move_object(bucket, plan_key, f"{SKIPPED_PREFIX}/{plan_key}")
                return "skipped"
        except (ClientError, ValueError, KeyError) as e:
            # leave the plan where it is so it can be retried
            logger.error(f" - ERR plan {plan_key} could not be applied: {e}")
            return "failed"

    pending = set()
    plan_iterator = iter(plan_keys)
    no_more_plans = False

    with ThreadPoolExecutor(max_workers=EXECUTE_THREADS) as executor:
        while pending or not no_more_plans:
            while not no_more_plans and len(pending) < EXECUTE_THREADS:
                try:
                    plan_key = next(plan_iterator)
                except StopIteration:
                    no_more_plans = True
                    break

                if out_of_time(context, EXECUTE_TIME_RESERVE_MS):
                    stopped_reason = "time"
                    no_more_plans = True
                    break

                pending.add(executor.submit(execute_plan, plan_key))

            if pending:
                completed, pending = wait(pending, return_when=FIRST_COMPLETED)
                for future in completed:
                    result = future.result()
                    if result == "deleted":
                        deleted += 1
                    elif result == "skipped":
                        skipped += 1
                    else:
                        failed += 1

    summary = {
        "deleted": deleted,
        "skipped": skipped,
        "failed": failed,
        "plans_remaining": len(plan_keys) - deleted - skipped - failed,
        "stopped_reason": stopped_reason,
        "duration_seconds": round(time.perf_counter() - script_start, 3),
    }
    logger.info(f"DELETION EXECUTION SUMMARY: {json.dumps(summary)}")
    return summary
