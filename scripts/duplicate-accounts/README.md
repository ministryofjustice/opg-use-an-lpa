# Merge duplicate accounts

This Read me explains the Layer 1 plan for the merge duplicate account functionality.

### Objective:
Find duplicate OneLogin identities from the ActorUsers table and export them as a JSON dataset to S3.


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

### Requirements for the script
Script name: TransformAthenaCSVToJson.py
```
boto3
```

This script performs the below:

1. Connect to DynamoDB
2. Scan ActorUsers table
3. Group users by Identity
4. Keep only duplicate identities
5. Build JSON output
6. Upload JSON file to S3

### Script output
```
s3://duplicate-accounts-bucket/inputs/duplicate-identities-20260521T184500Z.json
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

### Athena output files

```

```
