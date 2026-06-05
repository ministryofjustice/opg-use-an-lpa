# Merge duplicate accounts
This script identifies duplicate OneLogin identities from the `ActorUsers` Athena table and exports the duplicate account dataset to S3 in JSON format.

The exported dataset is intended to act as the source input for downstream duplicate account merge planning workflows.

This script forms Layer 1 of the duplicate account merge process.

## Objective
Find duplicate OneLogin identities from the ActorUsers table and export them as a JSON dataset to S3.

## High Level Flow

These two scripts performs the following steps:

1. Populate and fill the Athena database `ual` with up-to-date data from DynamoDB
2. Execute an Athena query against the `ActorUsers` dataset
3. Identify duplicate OneLogin identities
4. Group associated account IDs by identity
5. Generate a JSON dataset
6. Upload the dataset to S3

## Athena Query
```
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
```

The query should give rows like:

```
identity-1,user-a
identity-1,user-b
identity-2,user-c
```

THe Python script should transform csv result into JSON format


## Configuration

Configuration of the scripts are done via command line flags.

```shell
$ python dynamodb_export.py --help
usage: dynamodb_export.py [-h] [--environment ENVIRONMENT] [--check_exports]

Exports DynamoDB tables to S3.

options:
  -h, --help            show this help message and exit
  --environment ENVIRONMENT
                        The environment to export DynamoDB data for
  --check_exports       Output json data instead of plaintext to terminal

$ python discover_duplicates.py --help
usage: discover_duplicates.py [-h] [-d D] [-b B] [-p P]

Retrieve duplicated accounts from Athena and write out work files for deduplication

options:
  -h, --help  show this help message and exit
  -d D        The athena database to query against.
  -b B        The bucket to which to write the work files.
  -p P        Bucket prefix for work files.
```

For production you would likely use `-d ual -b use-a-lpa-dynamodb-exports-production`

# Running the scripts

## Python dependencies
The scripts need boto3 installed and available. On Cloudshell this is as simple as installing it before use.

```shell
$ git clone https://github.com/ministryofjustice/opg-use-an-lpa.git
$ cd opg-use-an-lpa/scripts/duplicate-accounts
$ pip install boto3
$ python dynamodb_export.py --environment <ENVIRONMENT>
$ python export_duplicate_identities.py -d <DATABASE> -b use-a-lpa-dynamodb-exports-<ENVIRONMENT>
```
The script must be run from a CloudShell.

### Athena output files

Athena writes temporary query result files to:
```
s3://<bucket>/athena-results/
```
These files are intermediary Athena artifacts and are separate from the final exported JSON dataset.

### Final Script output

The final JSON dataset is uploaded to:
```
s3://<bucket>/<prefix>/duplicate-identities-1.json
s3://<bucket>/<prefix>/duplicate-identities-2.json
s3://<bucket>/<prefix>/duplicate-identities-3.json
s3://<bucket>/<prefix>/duplicate-identities-n.json
```

### Contents of JSON file should look like

```
[
  {
    "identity": "urn:fdc:mock-one-login:2023:YiB/vNlBsGVnQfvyrA3hMjOKnI1dlJBBECre/cxUf1A=",
    "user_ids": [
      "4dc2230e-e669-28c4-cfe3-82c9b3480d3b",
      "55dc3ed8-5d37-1714-f89b-b4d40d56656a"
    ]
  },
  {
    "identity": "urn:fdc:mock-one-login:2023:anotherIdentity",
    "user_ids": [
      "user-c",
      "user-d",
      "user-e"
    ]
  }
]
```
This file becomes the input dataset for the Planner Lambda (Layer 2).
