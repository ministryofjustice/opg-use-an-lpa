"""
Delete unused accounts - accounts that have not been logged into for N months (default 6), or never logged into,
and have no associated LPAs.

Structured like the merge-duplicate-identities lambda it replaces:

  1. scripts/unused_accounts/dynamodb_export.py exports DynamoDB to S3 and loads it into Athena.
  2. scripts/unused_accounts/discover_unused_accounts.py runs the Athena query and writes work files to
     s3://<bucket>/<work prefix>/unused-accounts-<n>.json
  3. This lambda, with Execute=false, reads the work files, re-checks every account against DynamoDB (the export
     may be out of date) and writes a deletion plan per account to s3://<bucket>/<plan prefix>/ for review.
  4. This lambda, with Execute=true, re-checks every plan against current data and, if nothing has changed,
     deletes the account. Plans are then moved to done/ (deleted) or skipped/ (no longer eligible).

No table scans are performed - only GetItem/Query against the accounts named in the work files.

ActorUsers also holds "IDENTITY#<identity>" pinning rows, written by the API (service-api ActorUsers::add) to stop a
second account being created for the same OneLogin identity. They are not accounts and are never planned for deletion
on their own. When an account is deleted, its pinning row is deleted in the same transaction (as ActorUsers::delete
does), unless another account still has the same identity, in which case the pin is left in place for that account.
"""

import calendar
import hashlib
import json
import logging
import os
import time
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

# Create connections to AWS services
dynamodb = None
dynamodb_client = None
s3 = None


def initialise():
    global dynamodb, dynamodb_client, s3

    if "AWS_ENDPOINT_DYNAMODB" in os.environ:
        dynamodb = boto3.resource(
            "dynamodb",
            region_name="eu-west-1",
            endpoint_url=os.environ["AWS_ENDPOINT_DYNAMODB"],
        )
        dynamodb_client = boto3.client(
            "dynamodb",
            region_name="eu-west-1",
            endpoint_url=os.environ["AWS_ENDPOINT_DYNAMODB"],
        )
    else:
        dynamodb = boto3.resource("dynamodb", region_name="eu-west-1")
        dynamodb_client = boto3.client("dynamodb", region_name="eu-west-1")

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


def get_actor_users_table(prefix=None):
    return dynamodb.Table(get_table_name(prefix, "ActorUsers"))


def get_lpa_table(prefix=None):
    return dynamodb.Table(get_table_name(prefix, "UserLpaActorMap"))


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


def move_object(bucket, key, destination_prefix):
    s3.copy_object(Bucket=bucket, CopySource=f"{bucket}/{key}", Key=f"{destination_prefix}/{key}")
    s3.delete_object(Bucket=bucket, Key=key)


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


def load_work_items(bucket, work_prefix):
    """Load all account records from the work files written by discover_unused_accounts.py."""
    keys = sorted(list_keys(bucket, f"{work_prefix}/{WORK_FILE_NAME}-"), key=work_file_number)

    if not keys:
        logger.info(f" - No work files found in s3://{bucket}/{work_prefix}/")
        return []

    items = []
    for key in keys:
        items.extend(load_from_s3(bucket, key))

    logger.info(f" - Loaded {len(items)} account(s) from {len(keys)} work file(s)")
    return items


# ---------------------------------------------------------------------------------------------------------------------
# Plan
# ---------------------------------------------------------------------------------------------------------------------

def build_deletion_plans(table_prefix, bucket, work_prefix, plan_prefix, months_inactive):
    """Re-check every account in the work files and write a deletion plan for each one still eligible."""
    script_start = time.perf_counter()
    threshold = inactivity_threshold(months_inactive)

    logger.info(f"Loading work items (inactive = last login on or before {threshold}, or never)...")
    work_items = load_work_items(bucket, work_prefix)

    planned = 0
    skipped = 0
    for item in work_items:
        user_id = item.get("user_id")
        if not user_id:
            skipped += 1
            continue

        plan, skip_reason = assess_account(table_prefix, user_id, months_inactive, threshold)
        if plan is None:
            logger.info(f" - Skipping {user_id}: {skip_reason}")
            skipped += 1
            continue

        save_deletion_plan(bucket, plan_prefix, plan)
        planned += 1

    summary = {
        "work_items": len(work_items),
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


def execute_all_deletion_plans(bucket, plan_prefix, table_prefix, months_inactive):
    """Execute every plan in s3://<bucket>/<plan_prefix>/."""
    script_start = time.perf_counter()
    threshold = inactivity_threshold(months_inactive)

    logger.info(f"Executing all deletion plans in \"{bucket}/{plan_prefix}/\"")
    deleted = 0
    skipped = 0
    failed = 0

    # list up front so moving plans to done/ or skipped/ doesn't affect pagination
    for plan_key in list(list_keys(bucket, f"{plan_prefix}/")):
        try:
            plan = load_from_s3(bucket, plan_key)
            result, reason = execute_deletion_plan(table_prefix, plan, months_inactive, threshold)

            if result == "deleted":
                move_object(bucket, plan_key, DONE_PREFIX)
                deleted += 1
            else:
                logger.info(f" - Skipping {plan.get('account_id')}: {reason}")
                move_object(bucket, plan_key, SKIPPED_PREFIX)
                skipped += 1
        except (ClientError, ValueError, KeyError) as e:
            # leave the plan where it is so it can be retried
            logger.error(f" - ERR plan {plan_key} could not be applied: {e}")
            failed += 1

    summary = {
        "deleted": deleted,
        "skipped": skipped,
        "failed": failed,
        "duration_seconds": round(time.perf_counter() - script_start, 3),
    }
    logger.info(f"DELETION EXECUTION SUMMARY: {json.dumps(summary)}")
    return summary

