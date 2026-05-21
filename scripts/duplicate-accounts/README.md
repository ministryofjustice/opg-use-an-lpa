# Merge duplicate accounts

This script identifies duplicate OneLogin identities from the `ActorUsers` Athena table and exports the duplicate account dataset to S3 in JSON format.

The exported dataset is intended to act as the source input for downstream duplicate account merge planning workflows.

This script forms Layer 1 of the duplicate account merge process.

### Objective:
Find duplicate OneLogin identities from the ActorUsers table and export them as a JSON dataset to S3.

### High Level Flow

The script performs the following steps:

1. Execute an Athena query against the `ActorUsers` dataset
2. Identify duplicate OneLogin identities
3. Group associated account IDs by identity
4. Generate a JSON dataset
5. Upload the dataset to S3


## Athena Query
```
SELECT DISTINCT
    Identity,
    Id
FROM ActorUsers
WHERE Identity IS NOT NULL
AND Identity IN (
    SELECT Identity
    FROM ActorUsers
    WHERE Identity IS NOT NULL
    GROUP BY Identity
    HAVING COUNT(*) > 1
)
ORDER BY Identity
```

The query should give rows like:

```
identity-1,user-a
identity-1,user-b
identity-2,user-c
```

THe Python script should transform csv result into JSON format

### Requirements

## AWS serviecs
Script needs access to
- Athena
- S3

Script name: TransformAthenaCSVToJson.py

## Python dependencies
```
pip install boto3
```

## AWS permissions
The executing role needs permissions to

- Athena
  - `athena:StartQueryExecution`
  - `athena:GetQueryExecution`
  - `athena:GetQueryResults`

- S3
  - `s3:PutObject`
  - `s3:GetObject`
  - `s3:ListBucket`

### Configuration

The following values are currently configured directly in the script:

```python
DATABASE = "your_athena_database"
OUTPUT_BUCKET = "duplicate-accounts-bucket"
OUTPUT_PREFIX = "inputs"
```

These should eventually become command-line arguments or environment variables.

# Running the Script

```
python export_duplicate_identities.py
```

The script must be run from a CloudShell or Cloud9 environment.

### Athena output files

Athena writes temporary query result files to:

```
s3://<bucket>/athena-results/
```

These files are intermediary Athena artifacts and are separate from the final exported JSON dataset.

### Final Script output

The final JSON dataset is uploaded to:

```
s3://<bucket>/<prefix>/duplicate-identities-<timestamp>.json
```

Example:
```
s3://duplicate-accounts-bucket/inputs/duplicate-identities-20260521T120000Z.json
```

### Contents of JSON file should look like

```
[
  {
    "identity": "urn:fdc:mock-one-login:2023:YiB/vNlBsGVnQfvyrA3hMjOKnI1dlJBBECre/cxUf1A=",
    "duplicate_count": 2,
    "user_ids": [
      "4dc2230e-e669-28c4-cfe3-82c9b3480d3b",
      "55dc3ed8-5d37-1714-f89b-b4d40d56656a"
    ]
  },
  {
    "identity": "urn:fdc:mock-one-login:2023:anotherIdentity",
    "duplicate_count": 3,
    "user_ids": [
      "user-c",
      "user-d",
      "user-e"
    ]
  }
]
```
This file becomes the input dataset for the Planner Lambda (Layer 2).

### Console logs:
```
eg:

    Starting Athena duplicate identity query

    QueryExecutionId: 12345678-abcd-1234-abcd-1234567890

    Fetching results

    Found 4000 duplicate identities

    Uploaded duplicate dataset:
    s3://duplicate-accounts-bucket/inputs/duplicate-identities-20260521T184500Z.json

    Complete
```
