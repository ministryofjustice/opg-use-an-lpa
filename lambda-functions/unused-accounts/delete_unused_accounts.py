import hashlib
import os
import boto3
from datetime import datetime, UTC, timedelta
import json
import time
import logging
from botocore.exceptions import ClientError
from boto3.dynamodb.conditions import Key

logger = logging.getLogger()
logger.setLevel("INFO")

# Create connections to AWS service
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


def get_actor_users_table(prefix=None):
    table_name = f"{prefix}-ActorUsers" if prefix else "ActorUsers"
    return dynamodb.Table(table_name)

def get_lpa_table(prefix=None):
    table_name = f"{prefix}-UserLpaActorMap" if prefix else "UserLpaActorMap"
    return dynamodb.Table(table_name)


def get_user_lpas_count(lpa_table, user_id):
    """Query UserLpaActorMap for the given user and return count of LPAs."""
    try:
        response = lpa_table.query(
            IndexName="UserIndex",
            KeyConditionExpression=Key("UserId").eq(user_id)
        )
        return len(response.get("Items", []))
    except ClientError as e:
        logger.error(f"Error querying LPAs for user {user_id}: {e}")
        return None


def parse_last_login(last_login_str):
    """Parse LastLogin ISO format string to datetime object."""
    if not last_login_str:
        return None
    try:
        parsed = datetime.fromisoformat(last_login_str.replace('Z', '+00:00'))
    except (ValueError, TypeError):
        return None

    # Some stored values have no offset; assume UTC so comparisons with aware datetimes work
    if parsed.tzinfo is None:
        parsed = parsed.replace(tzinfo=UTC)
    return parsed


def is_account_unused(actor, lpa_table, months_inactive):
    """Check if an account is unused (not logged in for X months and no LPAs)."""
    last_login_str = actor.get("LastLogin")
    last_login = parse_last_login(last_login_str)

    if not last_login:
        # No login record - treat as very old
        last_login = datetime.min.replace(tzinfo=UTC)

    # Calculate threshold: X months ago
    now = datetime.now(UTC)
    threshold = now - timedelta(days=months_inactive * 30)  # Approximate months as 30 days

    # Check if last login is before threshold
    if last_login < threshold:
        # Check if user has no LPAs
        lpa_count = get_user_lpas_count(lpa_table, actor["Id"])
        if lpa_count == 0:
            return True

    return False


def scan_unused_accounts(table_prefix, lpa_table, months_inactive):
    """
    Scan ActorUsers table to find accounts that haven't been logged into
    for X months and have no associated LPAs.
    """
    actor_table = get_actor_users_table(table_prefix)
    unused_accounts = []

    logger.info(f"Scanning for unused accounts (not logged in for {months_inactive} months)...")

    # Scan the table
    try:
        response = actor_table.scan()
        items = response.get("Items", [])

        # Handle pagination
        while "LastEvaluatedKey" in response:
            response = actor_table.scan(
                ExclusiveStartKey=response["LastEvaluatedKey"]
            )
            items.extend(response.get("Items", []))

        logger.info(f"Total accounts scanned: {len(items)}")

        # Filter for unused accounts
        for actor in items:
            if is_account_unused(actor, lpa_table, months_inactive):
                unused_accounts.append({
                    "Id": actor["Id"],
                    "Email": actor.get("Email", "N/A"),
                    "LastLogin": actor.get("LastLogin", "Never"),
                    "Identity": actor.get("Identity", "N/A"),
                })

        logger.info(f"Found {len(unused_accounts)} unused accounts")
        return unused_accounts

    except ClientError as e:
        logger.error(f"Error scanning ActorUsers table: {e}")
        return []


def get_deletion_plan_key(plan_prefix, account_id):
    """Generate S3 key for deletion plan using account ID."""
    filename = hashlib.md5(account_id.encode()).hexdigest()
    return f"{plan_prefix}/{filename}.json"


def build_deletion_plans(table_prefix, bucket, plan_prefix, months_inactive):
    """
    Scan for unused accounts and build deletion plans.
    Save plans to S3 for review before execution.
    """
    script_start = time.perf_counter()

    lpa_table = get_lpa_table(table_prefix)

    # Find unused accounts
    unused_accounts = scan_unused_accounts(table_prefix, lpa_table, months_inactive)

    if not unused_accounts:
        logger.info("No unused accounts found. Nothing to process.")
        return

    # Build and save deletion plans
    saved_plans = 0
    for account in unused_accounts:
        plan = {
            "account_id": account["Id"],
            "email": account["Email"],
            "last_login": account["LastLogin"],
            "identity": account["Identity"],
            "reason": f"No login for {months_inactive}+ months and no associated LPAs",
            "created_at": datetime.now(UTC).isoformat(),
        }

        # Save plan to S3
        key = get_deletion_plan_key(plan_prefix, account["Id"])
        try:
            s3.put_object(
                Bucket=bucket,
                Key=key,
                Body=json.dumps(plan, indent=2),
                ContentType="application/json",
                Metadata={
                    "account-id": account["Id"],
                    "email": account["Email"],
                }
            )
            saved_plans += 1
            logger.info(f"Plan saved to S3: s3://{bucket}/{key}")
        except ClientError as e:
            logger.error(f"Error saving plan to S3: {e}")

    # Print summary
    duration = time.perf_counter() - script_start
    logger.info(f"\n{'=' * 80}")
    logger.info("DELETION PLAN GENERATION SUMMARY")
    logger.info(f"{'=' * 80}")
    logger.info(f"Total unused accounts found: {len(unused_accounts)}")
    logger.info(f"Plans saved to S3: {saved_plans}")
    logger.info(f"Processing time: {duration:.3f} seconds")
    logger.info(f"{'=' * 80}")


def execute_deletion_plan(table_prefix, deletion_plan):
    """
    Execute a single deletion plan.
    Deletes an account from the ActorUsers table.
    """
    account_id = deletion_plan["account_id"]

    logger.info(f"\nExecuting deletion for account: {account_id} ({deletion_plan['email']})")

    try:
        dynamodb_client.transact_write_items(
            TransactItems=[
                {
                    "Delete": {
                        "TableName": get_actor_users_table(table_prefix).name,
                        "Key": {"Id": {"S": account_id}},
                        "ConditionExpression": "attribute_exists(Id)",
                    }
                }
            ]
        )
        logger.info(f"✓ Account deleted successfully")
        return True
    except ClientError as e:
        logger.error(f"✗ Error deleting account: {e}")
        return False


def iterate_deletion_plans(bucket, plan_prefix):
    """Iterate through deletion plans in S3."""
    prefix = f"{plan_prefix}/"

    paginator = s3.get_paginator('list_objects_v2')
    page_iterator = paginator.paginate(
        Bucket=bucket,
        Prefix=prefix,
        PaginationConfig={'PageSize': 100}
    )

    for page in page_iterator:
        plans = []

        if page.get("Contents"):
            for plan in page["Contents"]:
                plans.append(plan["Key"])

            yield plans


def load_plan_from_s3(bucket, plan_key):
    """Load a deletion plan from S3."""
    try:
        response = s3.get_object(
            Bucket=bucket,
            Key=plan_key
        )
        body = response["Body"].read()
        return json.loads(body)
    except ClientError as e:
        logger.error(f"Error loading plan from S3: {e}")
        return None


def move_plan_to_done(bucket, plan_key):
    """Move a plan from active to done folder after execution."""
    try:
        s3.copy_object(
            Bucket=bucket,
            CopySource=f"{bucket}/{plan_key}",
            Key=f"done/{plan_key}"
        )
        s3.delete_object(
            Bucket=bucket,
            Key=plan_key
        )
        logger.info(f"Plan moved to done folder")
    except ClientError as e:
        logger.error(f"Error moving plan to done folder: {e}")


def execute_all_deletion_plans(bucket, plan_prefix, table_prefix):
    """
    Load and execute all deletion plans from S3.
    """
    script_start = time.perf_counter()

    logger.info(f"Executing all deletion plans in \"{bucket}/{plan_prefix}/\"")
    executed = 0
    failed = 0

    for plan_keys in iterate_deletion_plans(bucket, plan_prefix):
        for plan_key in plan_keys:
            plan = load_plan_from_s3(bucket, plan_key)

            if not plan:
                logger.warning(f"Skipping invalid plan: {plan_key}")
                failed += 1
                continue

            try:
                if execute_deletion_plan(table_prefix, plan):
                    move_plan_to_done(bucket, plan_key)
                    executed += 1
                else:
                    failed += 1
            except Exception as e:
                logger.error(f"Error executing plan {plan_key}: {e}")
                failed += 1

    # Print summary
    duration = time.perf_counter() - script_start
    logger.info(f"\n{'=' * 80}")
    logger.info("DELETION EXECUTION SUMMARY")
    logger.info(f"{'=' * 80}")
    logger.info(f"Successfully executed: {executed}")
    logger.info(f"Failed: {failed}")
    logger.info(f"Total execution time: {duration:.3f} seconds")
    logger.info(f"{'=' * 80}")

