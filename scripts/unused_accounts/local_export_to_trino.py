"""
LOCAL TESTING ONLY - stand-in for dynamodb_export.py when running against LocalStack.

LocalStack Community supports neither DynamoDB export-to-S3 nor Athena, so this script:

  1. Scans the LocalStack DynamoDB tables and writes them to LocalStack S3 in the same layout and format as a
     real DynamoDB export (gzipped DYNAMODB_JSON lines: {"Item": {"Id": {"S": "..."}, ...}}).
  2. Creates the `ual` schema and tables in the local Trino container over that data, mirroring the Athena
     tables created by dynamodb_export.py (Athena engine v3 is Trino, so the discover query runs unchanged).

Then run discover_unused_accounts.py with `--engine trino` to produce the lambda's work files.
"""

import argparse
import gzip
import io
import json
import os

import boto3

from discover_unused_accounts import TrinoEngine

# Athena table name -> (DynamoDB table name, Trino column definition)
TABLES = {
    "actor_users": (
        "ActorUsers",
        "item ROW(id ROW(s VARCHAR), identity ROW(s VARCHAR), email ROW(s VARCHAR), lastlogin ROW(s VARCHAR))",
    ),
    "user_lpa_actor_map": (
        "UserLpaActorMap",
        "item ROW(id ROW(s VARCHAR), actorid ROW(s VARCHAR), siriusuid ROW(s VARCHAR), userid ROW(s VARCHAR))",
    ),
}

EXPORT_PREFIX = "local-export"

# Bucket used by the local Trino's file metastore (see local-config/trino/catalog/hive.properties)
METASTORE_BUCKET = "trino-metastore"


def export_table(dynamodb, s3, table_name, bucket, prefix):
    """Write all items of a DynamoDB table to S3 as a single gzipped DYNAMODB_JSON export file."""
    paginator = dynamodb.get_paginator("scan")

    buffer = io.BytesIO()
    count = 0
    with gzip.GzipFile(fileobj=buffer, mode="wb") as gz:
        for page in paginator.paginate(TableName=table_name):
            for item in page.get("Items", []):
                gz.write((json.dumps({"Item": item}) + "\n").encode("utf-8"))
                count += 1

    # replace any previous export
    existing = s3.list_objects_v2(Bucket=bucket, Prefix=f"{prefix}/").get("Contents", [])
    if existing:
        s3.delete_objects(Bucket=bucket, Delete={"Objects": [{"Key": o["Key"]} for o in existing]})

    s3.put_object(Bucket=bucket, Key=f"{prefix}/part-00000.json.gz", Body=buffer.getvalue())
    print(f" - Exported {count} item(s) from {table_name} to s3://{bucket}/{prefix}/")


def ensure_bucket(s3, bucket):
    try:
        s3.head_bucket(Bucket=bucket)
    except s3.exceptions.ClientError:
        s3.create_bucket(Bucket=bucket, CreateBucketConfiguration={"LocationConstraint": "eu-west-1"})


def main():
    parser = argparse.ArgumentParser(
        description="LOCAL ONLY: export LocalStack DynamoDB tables to S3 and load them into the local Trino")
    parser.add_argument("-b", required=True, help="Bucket to write the export to")
    parser.add_argument("-d", default="ual", help="Trino schema (Athena database equivalent). default: ual")
    parser.add_argument("--table-prefix", default="", help="DynamoDB table prefix (empty for none)")
    parser.add_argument("--endpoint", default=os.environ.get("AWS_ENDPOINT_URL", "http://localhost:4566"),
                        help="LocalStack endpoint as seen from this machine. default: http://localhost:4566")
    parser.add_argument("--trino-url", default="http://localhost:8085", help="Trino URL. default: http://localhost:8085")
    parser.add_argument("--trino-catalog", default="hive", help="Trino catalog. default: hive")
    args = parser.parse_args()

    dynamodb = boto3.client("dynamodb", region_name="eu-west-1", endpoint_url=args.endpoint)
    s3 = boto3.client("s3", region_name="eu-west-1", endpoint_url=args.endpoint)
    ensure_bucket(s3, args.b)
    ensure_bucket(s3, METASTORE_BUCKET)

    print("Exporting DynamoDB tables to S3...")
    for athena_table, (dynamo_table, _) in TABLES.items():
        full_name = f"{args.table_prefix}-{dynamo_table}" if args.table_prefix else dynamo_table
        export_table(dynamodb, s3, full_name, args.b, f"{EXPORT_PREFIX}/{full_name}/data")

    print("Creating Trino tables...")
    trino = TrinoEngine(args.trino_url, args.trino_catalog, args.d)
    trino.run(f"CREATE SCHEMA IF NOT EXISTS {args.trino_catalog}.{args.d}")
    for athena_table, (dynamo_table, columns) in TABLES.items():
        full_name = f"{args.table_prefix}-{dynamo_table}" if args.table_prefix else dynamo_table
        location = f"s3://{args.b}/{EXPORT_PREFIX}/{full_name}/data"
        qualified = f"{args.trino_catalog}.{args.d}.{athena_table}"

        # external tables: dropping only removes the definition, not the exported data
        trino.run(f"DROP TABLE IF EXISTS {qualified}")
        trino.run(
            f"CREATE TABLE {qualified} ({columns}) "
            f"WITH (format = 'OPENX_JSON', external_location = '{location}')"
        )
        count = trino.run(f"SELECT count(*) FROM {qualified}")[0][0]
        print(f" - {qualified}: {count} row(s)")

    print("Complete")


if __name__ == "__main__":
    main()
