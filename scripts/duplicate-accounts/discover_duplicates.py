import argparse
import boto3
import json
import time
from collections import defaultdict

QUERY = """
WITH duplicate_identities AS (
    SELECT "Item"."identity"
    FROM "ual"."actor_users"
    WHERE "Item"."identity" IS NOT NULL
    GROUP BY "Item"."identity"
    HAVING COUNT(*) > 1
)

SELECT
    a."Item"."identity",
    a."Item"."id"
FROM "ual"."actor_users" a
JOIN duplicate_identities d
ON a."Item"."identity" = d."identity"
ORDER BY a."Item"."identity"
"""

# Create connections to AWS service
athena = boto3.client("athena", region_name="eu-west-1")
s3 = boto3.client("s3", region_name="eu-west-1")

# Run Athena query
def run_athena_query(database, bucket_name):
    response = athena.start_query_execution(
        QueryString=QUERY,
        QueryExecutionContext={"Database": database},
        ResultConfiguration={"OutputLocation": f"s3://{bucket_name}/athena-output"}
    )

    return response["QueryExecutionId"]

# Wait for Athena to complete
def wait_for_query(query_execution_id):
    while True:
        response = athena.get_query_execution(
            QueryExecutionId=query_execution_id
        )

        status = response["QueryExecution"]["Status"]["State"]

        if status in ["SUCCEEDED", "FAILED", "CANCELLED"]:
            return status

        time.sleep(2)


def fetch_results(query_execution_id, prefix, bucket_name):
    paginator         = athena.get_paginator("get_query_results")
    grouped           = defaultdict(list)
    first_row         = True
    identity_complete = False

    page_count      = 1
    duplicate_count = 0
    wip_identity   = ''
    for page in paginator.paginate(
            QueryExecutionId=query_execution_id,
            PaginationConfig={
                'PageSize': 100
            }
    ):
        for row in page["ResultSet"]["Rows"]:
            # skip header row (only exists on first page)
            if first_row:
                first_row = False
                continue

            data = row["Data"]

            identity = (data[0].get("VarCharValue")).removeprefix("{s=").removesuffix("}")
            user_id = (data[1].get("VarCharValue")).removeprefix("{s=").removesuffix("}")

            if identity != wip_identity:
                identity_complete = True
                wip_identity      = identity
            else:
                identity_complete = False

            if len(grouped) == 100 and identity_complete:
                persist_identities(grouped, page_count, prefix, bucket_name)
                grouped = defaultdict(list)
                page_count += 1

            grouped[identity].append(user_id)
            duplicate_count += 1

    if len(grouped) > 0:
        persist_identities(grouped, page_count, prefix, bucket_name)

    return duplicate_count

def persist_identities(grouped, page_count, prefix, bucket_name):
    paged_results = []

    for identity, user_ids in grouped.items():
        paged_results.append({
            "identity": identity,
            "user_ids": sorted(user_ids)
        })

    upload_json(paged_results, f"{prefix}/duplicate-identities-{page_count}", bucket_name)

# Upload Json to S3
def upload_json(results, prefix, bucket_name):
    key = f"{prefix}.json"

    s3.put_object(
        Bucket=bucket_name,
        Key=key,
        Body=json.dumps(results, indent=2),
        ContentType="application/json"
    )

    print(f"Uploaded: s3://{bucket_name}/{key}")


def main():
    parser = argparse.ArgumentParser(
        description="Retrieve duplicated accounts from Athena and write out work files for deduplication")
    parser.add_argument("-d", type=str, default="",
                        help="The athena database to query against.")
    parser.add_argument("-b", type=str,
                        help="The bucket to which to write the work files.")
    parser.add_argument("-p", default="todo", type=str,
                        help="Bucket prefix for work files.")
    args = parser.parse_args()

    print("Starting Athena duplicate identity query")
    query_execution_id = run_athena_query(args.d, args.b)

    print(f"QueryExecutionId: {query_execution_id}")
    status = wait_for_query(query_execution_id)

    if status != "SUCCEEDED":
        raise Exception(f"Athena query failed with status: {status}")

    print("Fetching results")
    count = fetch_results(query_execution_id, args.p, args.b)

    print(f"Found {count} duplicate identities")
    print("Complete")

if __name__ == "__main__":
    main()
