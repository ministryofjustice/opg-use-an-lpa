import boto3
import json
import time
from collections import defaultdict
from datetime import datetime, UTC

# Below configuration variables needs to become arguments
DATABASE = "your_athena_database"
OUTPUT_BUCKET = "duplicate-accounts-bucket"
OUTPUT_PREFIX = "inputs"

QUERY = """
WITH duplicate_identities AS (
    SELECT Identity
    FROM ActorUsers
    WHERE Identity IS NOT NULL
    GROUP BY Identity
    HAVING COUNT(*) > 1
)

SELECT
    a.Identity,
    a.Id
FROM ActorUsers a
JOIN duplicate_identities d
ON a.Identity = d.Identity
ORDER BY a.Identity
"""

# Create connections to AWS service
athena = boto3.client("athena")
s3 = boto3.client("s3")


# Run Athena query
def run_athena_query():
    response = athena.start_query_execution(
        QueryString=QUERY,
        QueryExecutionContext={
            "Database": DATABASE
        },
        ResultConfiguration={
            "OutputLocation": f"s3://{OUTPUT_BUCKET}/athena-results/"
        }
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


def fetch_results(query_execution_id):
    paginator = athena.get_paginator("get_query_results")

    grouped = defaultdict(list)

    first_row = True

    # Loop through pages
    for page in paginator.paginate(QueryExecutionId=query_execution_id):

        for row in page["ResultSet"]["Rows"]:

            # skip header row
            if first_row:
                first_row = False
                continue

            data = row["Data"]

            identity = data[0].get("VarCharValue")
            user_id = data[1].get("VarCharValue")

            grouped[identity].append(user_id)

    results = []

    # Build Json object
    for identity, user_ids in grouped.items():
        results.append({
            "identity": identity,
            "duplicate_count": len(user_ids),
            "user_ids": sorted(user_ids)
        })

    return results

# Upload Json to S3
def upload_json(results):
    timestamp = datetime.now(UTC).strftime("%Y%m%dT%H%M%SZ")

    key = f"{OUTPUT_PREFIX}/duplicate-identities-{timestamp}.json"

    s3.put_object(
        Bucket=OUTPUT_BUCKET,
        Key=key,
        Body=json.dumps(results, indent=2),
        ContentType="application/json"
    )

    print(f"Uploaded duplicate dataset:")
    print(f"s3://{OUTPUT_BUCKET}/{key}")


def main():
    print("Starting Athena duplicate identity query")

    query_execution_id = run_athena_query()

    print(f"QueryExecutionId: {query_execution_id}")

    status = wait_for_query(query_execution_id)

    if status != "SUCCEEDED":
        raise Exception(f"Athena query failed with status: {status}")

    print("Fetching results")

    results = fetch_results(query_execution_id)

    print(f"Found {len(results)} duplicate identities")

    upload_json(results)

    print("Complete")


if __name__ == "__main__":
    main()
