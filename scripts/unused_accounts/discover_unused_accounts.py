"""
Find unused accounts with an Athena query and write work files for the unused-accounts lambda.

An account is "unused" when:
  - it is a real account, not an "IDENTITY#<identity>" pinning row (see service-api ActorUsers::add), and
  - it has no rows in UserLpaActorMap (no LPAs), and
  - it has never logged in, or its LastLogin date is on or before the date N months ago (default 6).

Work files are written to s3://<bucket>/<prefix>/unused-accounts-<n>.json, each containing up to 100 records:

  [
    {"user_id": "...", "identity": "...", "email": "...", "last_login": "2025-11-14T08:37:47Z"},
    ...
  ]

The lambda (lambda-functions/unused-accounts) reads these files, re-checks every account against
DynamoDB, and produces deletion plans for review before anything is deleted.

The query is written in Trino SQL, which is what Athena (engine v3) runs. For local testing the same
query can be run against a local Trino container with `--engine trino` (see test_unused_accounts.sh).
"""

import argparse
import json
import os
import time
import urllib.request

import boto3

WORK_FILE_NAME = "unused-accounts"
RECORDS_PER_WORK_FILE = 100


def build_unused_accounts_query(months_inactive=6):
    """
    Trino/Athena SQL returning one row per unused account.

    LastLogin is stored as an ISO 8601 string, with or without a time zone and fractional seconds
    (e.g. "2025-11-14T08:37:47Z" or "2026-10-14T10:33:46.706171"), so we compare its date part as a
    string. ISO dates sort lexicographically, so this avoids timestamp parsing differences.

    "No LPAs" is an anti-join (LEFT JOIN ... IS NULL) because Trino/Athena does not support the equivalent
    correlated NOT EXISTS subquery over these nested columns.
    """
    months_inactive = int(months_inactive)

    return f"""
SELECT
    a.item.id.s        AS user_id,
    a.item.identity.s  AS "identity",
    a.item.email.s     AS email,
    a.item.lastlogin.s AS last_login
FROM actor_users a
LEFT JOIN (
    SELECT DISTINCT m.item.userid.s AS user_id
    FROM user_lpa_actor_map m
    WHERE m.item.userid.s IS NOT NULL
) lpas
  ON lpas.user_id = a.item.id.s
WHERE a.item.id.s IS NOT NULL
  -- exclude the API's IDENTITY#<identity> pinning rows: they are not accounts (no LastLogin, no LPAs)
  AND a.item.id.s NOT LIKE 'IDENTITY#%'
  AND lpas.user_id IS NULL
  AND (
        a.item.lastlogin.s IS NULL
     OR a.item.lastlogin.s = ''
     OR substr(a.item.lastlogin.s, 1, 10) <= CAST(date_add('month', -{months_inactive}, current_date) AS varchar)
  )
ORDER BY a.item.id.s
"""


# ---------------------------------------------------------------------------------------------------------------------
# Query engines - both return a list of rows, each row a list of string values (None for NULL), without a header row
# ---------------------------------------------------------------------------------------------------------------------

class AthenaEngine:
    def __init__(self, database, output_location, region="eu-west-1"):
        self.database = database
        self.output_location = output_location
        self.athena = boto3.client("athena", region_name=region)

    def run(self, sql):
        response = self.athena.start_query_execution(
            QueryString=sql,
            QueryExecutionContext={"Database": self.database},
            ResultConfiguration={"OutputLocation": self.output_location},
        )
        query_execution_id = response["QueryExecutionId"]
        print(f"QueryExecutionId: {query_execution_id}")

        while True:
            execution = self.athena.get_query_execution(QueryExecutionId=query_execution_id)["QueryExecution"]
            state = execution["Status"]["State"]
            if state in ["SUCCEEDED", "FAILED", "CANCELLED"]:
                break
            time.sleep(2)

        if state != "SUCCEEDED":
            reason = execution["Status"].get("StateChangeReason", "")
            raise Exception(f"Athena query failed with status {state}: {reason}")

        rows = []
        first_row = True
        paginator = self.athena.get_paginator("get_query_results")
        for page in paginator.paginate(QueryExecutionId=query_execution_id):
            for row in page["ResultSet"]["Rows"]:
                # skip header row (only on the first page)
                if first_row:
                    first_row = False
                    continue
                rows.append([cell.get("VarCharValue") for cell in row["Data"]])

        return rows


class TrinoEngine:
    """Minimal client for Trino's REST protocol, so no extra dependencies are needed."""

    def __init__(self, url, catalog, schema, user="unused-accounts"):
        self.url = url.rstrip("/")
        self.headers = {
            "X-Trino-User": user,
            "X-Trino-Catalog": catalog,
            "X-Trino-Schema": schema,
        }

    def _request(self, url, data=None):
        method = "POST" if data is not None else "GET"
        request = urllib.request.Request(url, data=data, headers=self.headers, method=method)
        with urllib.request.urlopen(request, timeout=60) as response:
            return json.loads(response.read())

    def run(self, sql):
        result = self._request(f"{self.url}/v1/statement", data=sql.encode("utf-8"))
        rows = []

        while True:
            if "error" in result:
                error = result["error"]
                raise Exception(f"Trino query failed: {error.get('errorName')}: {error.get('message')}")

            rows.extend(result.get("data", []))

            next_uri = result.get("nextUri")
            if not next_uri:
                break
            if not result.get("data"):
                time.sleep(0.1)
            result = self._request(next_uri)

        return [[None if value is None else str(value) for value in row] for row in rows]


# ---------------------------------------------------------------------------------------------------------------------
# Work files
# ---------------------------------------------------------------------------------------------------------------------

def rows_to_records(rows):
    return [
        {
            "user_id": user_id,
            "identity": identity,
            "email": email,
            "last_login": last_login or None,
        }
        for user_id, identity, email, last_login in rows
    ]


def clear_work_files(s3, bucket, prefix):
    """Remove work files from a previous run so stale accounts are not processed again."""
    paginator = s3.get_paginator("list_objects_v2")
    removed = 0
    for page in paginator.paginate(Bucket=bucket, Prefix=f"{prefix}/{WORK_FILE_NAME}-"):
        keys = [{"Key": obj["Key"]} for obj in page.get("Contents", [])]
        if keys:
            s3.delete_objects(Bucket=bucket, Delete={"Objects": keys})
            removed += len(keys)

    if removed:
        print(f"Removed {removed} work file(s) from a previous run")


def write_work_files(s3, bucket, prefix, records):
    clear_work_files(s3, bucket, prefix)

    file_count = 0
    for start in range(0, len(records), RECORDS_PER_WORK_FILE):
        file_count += 1
        key = f"{prefix}/{WORK_FILE_NAME}-{file_count}.json"
        s3.put_object(
            Bucket=bucket,
            Key=key,
            Body=json.dumps(records[start:start + RECORDS_PER_WORK_FILE], indent=2),
            ContentType="application/json",
        )

    print(f"Uploaded {file_count} work file(s) to s3://{bucket}/{prefix}/{WORK_FILE_NAME}-<n>.json")
    return file_count


def s3_client(endpoint_url=None, region="eu-west-1"):
    endpoint_url = endpoint_url or os.environ.get("AWS_ENDPOINT_S3")
    if endpoint_url:
        return boto3.client("s3", region_name=region, endpoint_url=endpoint_url)
    return boto3.client("s3", region_name=region)


def main():
    parser = argparse.ArgumentParser(
        description="Find unused accounts with Athena and write out work files for the unused-accounts lambda")
    parser.add_argument("-d", default="ual", type=str,
                        help="The Athena database (or Trino schema) to query against. default: ual")
    parser.add_argument("-b", required=True, type=str,
                        help="The bucket to which to write the work files.")
    parser.add_argument("-p", default="todo", type=str,
                        help="Bucket prefix for work files. default: todo")
    parser.add_argument("--months-inactive", default=6, type=int,
                        help="Accounts not logged into for this many months are unused. default: 6")
    parser.add_argument("--engine", choices=["athena", "trino"], default="athena",
                        help="Query engine. Use 'trino' for local testing. default: athena")
    parser.add_argument("--trino-url", default="http://localhost:8085",
                        help="Trino URL when --engine trino. default: http://localhost:8085")
    parser.add_argument("--trino-catalog", default="hive",
                        help="Trino catalog when --engine trino. default: hive")
    parser.add_argument("--s3-endpoint", default=None,
                        help="Override the S3 endpoint (e.g. LocalStack). Defaults to $AWS_ENDPOINT_S3 if set.")
    args = parser.parse_args()

    if args.engine == "trino":
        engine = TrinoEngine(args.trino_url, args.trino_catalog, args.d)
    else:
        engine = AthenaEngine(args.d, f"s3://{args.b}/athena-output")

    print(f"Starting {args.engine} unused accounts query (inactive for {args.months_inactive}+ months, no LPAs)")
    rows = engine.run(build_unused_accounts_query(args.months_inactive))
    records = rows_to_records(rows)
    print(f"Found {len(records)} unused account(s)")

    write_work_files(s3_client(args.s3_endpoint), args.b, args.p, records)
    print("Complete")


if __name__ == "__main__":
    main()


