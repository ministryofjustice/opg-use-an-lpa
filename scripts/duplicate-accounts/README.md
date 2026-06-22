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
7. Process work file dataset into merge plans using `merge_duplicate_identities.py`
8. Run merge plans using `merge_duplicate_identities --execute`

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

$ python ./merge_duplicate_identities.py --help
usage: merge_duplicate_identities.py [-h] [--limit LIMIT] [--offset OFFSET]
                                     [--table-prefix TABLE_PREFIX] [--bucket BUCKET]
                                     [--work-prefix WORK_PREFIX]
                                     [--plan-prefix PLAN_PREFIX]
                                     [--plan-file PLAN_FILE]
                                     [--execute]

Merge duplicate OPG identities

options:
  -h, --help            show this help message and exit
  --limit LIMIT         Limit number of duplicate identities processed
  --offset OFFSET       Skip this many duplicate identities before processing
  --table-prefix TABLE_PREFIX
                        The DynamoDB table prefix to use when querying. default: demo
  --bucket BUCKET       The bucket containing merge plans and work files
  --work-prefix WORK_PREFIX
                        Bucket prefix for work files. default: todo
  --plan-prefix PLAN_PREFIX
                        Bucket prefix for merge plan files. default: plan
  --plan-file PLAN_FILE
                        Specify a specific merge plan file to run
  --execute             Apply a reviewed merge plan
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

## Discover duplicates Script output

The final work plan JSON dataset is uploaded to:
```
s3://<bucket>/<prefix>/duplicate-identities-1.json
s3://<bucket>/<prefix>/duplicate-identities-2.json
s3://<bucket>/<prefix>/duplicate-identities-3.json
s3://<bucket>/<prefix>/duplicate-identities-n.json
```

### Contents of work plan JSON should look like

```json
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
  ...
]
```

## Processing work files into merge plans
Using the `merge_duplicate_identities.py` script you can process the work files into merge plans.

The bucket and table prefix must be provided using the `--bucket` and `--table-prefix` flags respectively.
Table prefix can vary depending on the environment - see DynamoDB to ensure you have the correct one for your environment.

```shell
$ python merge_duplicate_identities.py --bucket use-a-lpa-dynamodb-exports-<ENVIRONMENT> --table-prefix <TABLE_PREFIX>

# using --limit and --offset flags to limit the number of work files processed
# starting at the 10th work file entry process an additional 10 entries.
$ python merge_duplicate_identities.py --bucket use-a-lpa-dynamodb-exports-<ENVIRONMENT> --table-prefix <TABLE_PREFIX> --limit 10 --offset 10
```

## Applying merge plans
The same script can be used to apply merge plans using the `--execute` flag.

```shell
$ python merge_duplicate_identities.py --bucket use-a-lpa-dynamodb-exports-<ENVIRONMENT> --execute
```
