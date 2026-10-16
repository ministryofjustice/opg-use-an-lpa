import json
from datetime import date

import boto3
import pytest
from moto import mock_aws

import delete_unused_accounts as dua


BUCKET = "test-bucket"
PREFIX = "test"


# ---------------------------------------------------------------------------------------------------------------------
# Pure functions
# ---------------------------------------------------------------------------------------------------------------------

@pytest.mark.parametrize(
    "today, months, expected",
    [
        (date(2026, 10, 14), 6, date(2026, 4, 14)),
        (date(2026, 3, 15), 6, date(2025, 9, 15)),   # crosses a year boundary
        (date(2026, 8, 31), 6, date(2026, 2, 28)),   # clamps to the end of a shorter month
        (date(2028, 8, 31), 6, date(2028, 2, 29)),   # leap year
        (date(2026, 1, 1), 12, date(2025, 1, 1)),
    ],
)
def test_subtract_months(today, months, expected):
    assert dua.subtract_months(today, months) == expected


@pytest.mark.parametrize(
    "last_login, expected",
    [
        (None, True),                           # never logged in
        ("", True),
        ("2026-04-13T23:59:59Z", True),         # before threshold
        ("2026-04-14T10:00:00Z", True),         # on the threshold date
        ("2026-04-15T00:00:00Z", False),        # after threshold
        ("2026-10-14T10:33:46.706171", False),  # no time zone, fractional seconds
    ],
)
def test_is_inactive(last_login, expected):
    assert dua.is_inactive(last_login, "2026-04-14") is expected


# ---------------------------------------------------------------------------------------------------------------------
# DynamoDB / S3 behaviour
# ---------------------------------------------------------------------------------------------------------------------

@pytest.fixture
def aws(monkeypatch):
    monkeypatch.setenv("AWS_ACCESS_KEY_ID", "testing")
    monkeypatch.setenv("AWS_SECRET_ACCESS_KEY", "testing")
    monkeypatch.setenv("AWS_DEFAULT_REGION", "eu-west-1")
    monkeypatch.delenv("AWS_ENDPOINT_DYNAMODB", raising=False)
    monkeypatch.delenv("AWS_ENDPOINT_S3", raising=False)

    with mock_aws():
        ddb = boto3.client("dynamodb", region_name="eu-west-1")
        ddb.create_table(
            TableName=f"{PREFIX}-ActorUsers",
            AttributeDefinitions=[
                {"AttributeName": "Id", "AttributeType": "S"},
                {"AttributeName": "Identity", "AttributeType": "S"},
            ],
            KeySchema=[{"AttributeName": "Id", "KeyType": "HASH"}],
            GlobalSecondaryIndexes=[{
                "IndexName": "IdentityIndex",
                "KeySchema": [{"AttributeName": "Identity", "KeyType": "HASH"}],
                "Projection": {"ProjectionType": "ALL"},
            }],
            BillingMode="PAY_PER_REQUEST",
        )
        ddb.create_table(
            TableName=f"{PREFIX}-UserLpaActorMap",
            AttributeDefinitions=[
                {"AttributeName": "Id", "AttributeType": "S"},
                {"AttributeName": "UserId", "AttributeType": "S"},
            ],
            KeySchema=[{"AttributeName": "Id", "KeyType": "HASH"}],
            GlobalSecondaryIndexes=[{
                "IndexName": "UserIndex",
                "KeySchema": [{"AttributeName": "UserId", "KeyType": "HASH"}],
                "Projection": {"ProjectionType": "ALL"},
            }],
            BillingMode="PAY_PER_REQUEST",
        )
        s3 = boto3.client("s3", region_name="eu-west-1")
        s3.create_bucket(Bucket=BUCKET, CreateBucketConfiguration={"LocationConstraint": "eu-west-1"})

        dua.initialise()
        yield ddb, s3


def add_actor(ddb, user_id, last_login=None, identity=None):
    """Add an account and its IDENTITY# pinning row, as the API does (service-api ActorUsers::add)."""
    identity = identity or f"urn:{user_id}"
    item = {"Id": {"S": user_id}, "Email": {"S": f"{user_id}@example.org"}, "Identity": {"S": identity}}
    if last_login:
        item["LastLogin"] = {"S": last_login}
    ddb.put_item(TableName=f"{PREFIX}-ActorUsers", Item=item)
    ddb.put_item(TableName=f"{PREFIX}-ActorUsers", Item={"Id": {"S": f"IDENTITY#{identity}"}})


def add_lpa(ddb, user_id):
    ddb.put_item(
        TableName=f"{PREFIX}-UserLpaActorMap",
        Item={"Id": {"S": f"map-{user_id}"}, "UserId": {"S": user_id}},
    )


def write_work_file(s3, user_ids, number=1):
    s3.put_object(
        Bucket=BUCKET,
        Key=f"todo/unused-accounts-{number}.json",
        Body=json.dumps([{"user_id": u} for u in user_ids]),
    )


def keys(s3, prefix):
    return [o["Key"] for o in s3.list_objects_v2(Bucket=BUCKET, Prefix=prefix).get("Contents", [])]


def actor_exists(ddb, user_id):
    return "Item" in ddb.get_item(TableName=f"{PREFIX}-ActorUsers", Key={"Id": {"S": user_id}})


def test_plan_only_includes_accounts_that_are_still_unused(aws):
    ddb, s3 = aws
    add_actor(ddb, "old", "2020-01-01T00:00:00Z")
    add_actor(ddb, "never")
    add_actor(ddb, "recent", "2999-01-01T00:00:00Z")
    add_actor(ddb, "has-lpa", "2020-01-01T00:00:00Z")
    add_lpa(ddb, "has-lpa")
    write_work_file(s3, ["old", "never", "recent", "has-lpa", "deleted-already"])

    summary = dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)

    assert summary["work_items"] == 5
    assert summary["plans_saved"] == 2
    assert summary["skipped"] == 3
    plans = [json.loads(s3.get_object(Bucket=BUCKET, Key=k)["Body"].read()) for k in keys(s3, "plan/")]
    assert sorted(p["account_id"] for p in plans) == ["never", "old"]
    never = next(p for p in plans if p["account_id"] == "never")
    assert never["last_login"] is None
    assert never["reason"] == "Never logged in and no associated LPAs"


def test_plan_reads_all_work_files(aws):
    ddb, s3 = aws
    for i in range(3):
        add_actor(ddb, f"user-{i}", "2020-01-01T00:00:00Z")
        write_work_file(s3, [f"user-{i}"], number=i + 1)

    assert dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)["plans_saved"] == 3


def test_execute_deletes_unchanged_accounts_and_skips_changed_ones(aws):
    ddb, s3 = aws
    add_actor(ddb, "old", "2020-01-01T00:00:00Z")
    add_actor(ddb, "never")
    add_actor(ddb, "logs-in", "2020-01-01T00:00:00Z")
    add_actor(ddb, "gets-lpa", "2020-01-01T00:00:00Z")
    write_work_file(s3, ["old", "never", "logs-in", "gets-lpa"])
    dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)

    # activity after the plans were made
    add_actor(ddb, "logs-in", "2999-01-01T00:00:00Z")
    add_lpa(ddb, "gets-lpa")

    summary = dua.execute_all_deletion_plans(BUCKET, "plan", PREFIX, 6)

    assert summary == {**summary, "deleted": 2, "skipped": 2, "failed": 0}
    assert not actor_exists(ddb, "old")
    assert not actor_exists(ddb, "never")
    assert actor_exists(ddb, "logs-in")
    assert actor_exists(ddb, "gets-lpa")
    assert keys(s3, "plan/") == []
    assert len(keys(s3, "done/plan/")) == 2
    assert len(keys(s3, "skipped/plan/")) == 2


def test_execute_skips_when_plan_no_longer_matches(aws):
    ddb, s3 = aws
    add_actor(ddb, "old", "2020-01-01T00:00:00Z")
    write_work_file(s3, ["old"])
    dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)

    # still eligible, but a different (older) LastLogin than the plan recorded
    add_actor(ddb, "old", "2019-01-01T00:00:00Z")

    summary = dua.execute_all_deletion_plans(BUCKET, "plan", PREFIX, 6)

    assert summary["skipped"] == 1
    assert actor_exists(ddb, "old")


def test_identity_pins_are_never_planned(aws):
    ddb, s3 = aws
    add_actor(ddb, "old", "2020-01-01T00:00:00Z")
    write_work_file(s3, ["IDENTITY#urn:old"])

    summary = dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)

    assert summary["plans_saved"] == 0
    assert summary["skipped"] == 1
    assert actor_exists(ddb, "IDENTITY#urn:old")


def test_execute_deletes_identity_pin_with_account(aws):
    ddb, s3 = aws
    add_actor(ddb, "old", "2020-01-01T00:00:00Z")
    add_actor(ddb, "active", "2999-01-01T00:00:00Z")
    write_work_file(s3, ["old"])
    dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)

    plan = json.loads(s3.get_object(Bucket=BUCKET, Key=keys(s3, "plan/")[0])["Body"].read())
    assert plan["identity_pin"] == "IDENTITY#urn:old"

    assert dua.execute_all_deletion_plans(BUCKET, "plan", PREFIX, 6)["deleted"] == 1
    assert not actor_exists(ddb, "old")
    assert not actor_exists(ddb, "IDENTITY#urn:old")
    # other accounts and their pins are untouched
    assert actor_exists(ddb, "active")
    assert actor_exists(ddb, "IDENTITY#urn:active")


def test_execute_keeps_identity_pin_shared_with_another_account(aws):
    ddb, s3 = aws
    add_actor(ddb, "old", "2020-01-01T00:00:00Z", identity="urn:shared")
    add_actor(ddb, "active", "2999-01-01T00:00:00Z", identity="urn:shared")
    write_work_file(s3, ["old"])
    dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)

    plan = json.loads(s3.get_object(Bucket=BUCKET, Key=keys(s3, "plan/")[0])["Body"].read())
    assert plan["identity_pin"] is None

    assert dua.execute_all_deletion_plans(BUCKET, "plan", PREFIX, 6)["deleted"] == 1
    assert not actor_exists(ddb, "old")
    assert actor_exists(ddb, "active")
    assert actor_exists(ddb, "IDENTITY#urn:shared")


def test_execute_skips_when_identity_becomes_shared(aws):
    ddb, s3 = aws
    add_actor(ddb, "old", "2020-01-01T00:00:00Z", identity="urn:shared")
    write_work_file(s3, ["old"])
    dua.build_deletion_plans(PREFIX, BUCKET, "todo", "plan", 6)

    # another account with the same identity appears after the plan was made
    add_actor(ddb, "new", "2999-01-01T00:00:00Z", identity="urn:shared")

    summary = dua.execute_all_deletion_plans(BUCKET, "plan", PREFIX, 6)

    assert summary["skipped"] == 1
    assert actor_exists(ddb, "old")
    assert actor_exists(ddb, "IDENTITY#urn:shared")


