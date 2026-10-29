# Delete unused accounts
This script identifies unused accounts from the `ActorUsers` Athena table and exports the accounts dataset to S3 in JSON format.

The exported dataset is intended to act as the source input for downstream delete account planning workflows.

This script forms Layer 1 of the delete unused accounts process.

## Objective
Find unused accounts from the ActorUsers table and export them as a JSON dataset to S3.

## High Level Flow

The two scripts and the unused-accounts Lambda perform the following steps:

1. Populate and fill the Athena database `ual` with up-to-date data from DynamoDB
2. Execute an Athena query against the `ActorUsers` dataset
3. Identify unused accounts
4. Generate a JSON dataset
5. Upload the dataset to S3
6. Invoke the unused-accounts Lambda with `{}` to generate deletion plans from current DynamoDB data
7. Review the plans, then invoke the Lambda with `{"Execute": true}` to recheck and apply them

## Athena Query
```
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
```

The query should give rows like:

```
TBD
```


## Configuration

Configuration of the scripts are done via command line flags.

```shell
$ uv run dynamodb_export.py -h
usage: dynamodb_export.py [-h] [--environment ENVIRONMENT] [--check_exports]

Exports DynamoDB tables to S3.

options:
  -h, --help            show this help message and exit
  --environment ENVIRONMENT
                        The environment to export DynamoDB data for
  --check_exports       Wait for the latest existing export of each table without starting new exports

$ uv run discover_unused_accounts.py -h
usage: discover_unused_accounts.py [-h] [-d D] -b B [-p P] [--months-inactive MONTHS_INACTIVE] [--engine {athena,trino}] [--trino-url TRINO_URL] [--trino-catalog TRINO_CATALOG] [--s3-endpoint S3_ENDPOINT]

Find unused accounts with Athena and write out work files for the unused-accounts lambda

options:
  -h, --help            show this help message and exit
  -d D                  The Athena database (or Trino schema) to query against. default: ual
  -b B                  The bucket to which to write the work files.
  -p P                  Bucket prefix for work files. default: todo
  --months-inactive MONTHS_INACTIVE
                        Accounts not logged into for this many months are unused. default: 6
  --engine {athena,trino}
                        Query engine. Use 'trino' for local testing. default: athena
  --trino-url TRINO_URL
                        Trino URL when --engine trino. default: http://localhost:8085
  --trino-catalog TRINO_CATALOG
                        Trino catalog when --engine trino. default: hive
  --s3-endpoint S3_ENDPOINT
                        Override the S3 endpoint (e.g. LocalStack). Defaults to $AWS_ENDPOINT_S3 if set.
```

For production you would likely use `-d ual -b use-a-lpa-dynamodb-exports-production`

# Running the scripts

## Python dependencies
Use uv to install the Python version and dependencies declared in `pyproject.toml`.
Both scripts use the AWS credentials already available in their execution environment;
they do not assume roles. Locally, run them through `aws-vault exec <PROFILE> --`.

```shell
$ git clone https://github.com/ministryofjustice/opg-use-an-lpa.git
$ cd opg-use-an-lpa/scripts/unused_accounts
$ uv sync
$ uv run dynamodb_export.py --environment <ENVIRONMENT>
$ uv run discover_unused_accounts.py -d ual -b use-a-lpa-dynamodb-exports-<ENVIRONMENT>
```
The exporter waits for the exact exports it starts before rebuilding Athena's `ual`
database. `--check_exports` instead waits for each table's most recently started
existing export and does not rebuild Athena. Failed or missing exports stop the script.

Discovery stops if any previous work file cannot be deleted, rather than reporting
success with stale files still present.

## Generating and applying deletion plans

The deployed Lambda is configured with `BUCKET`, `ENVIRONMENT_NAME` (the table prefix),
`WORK_FILE_PREFIX` (default `todo`), `PLAN_FILE_PREFIX` (default `plan`), and
`MONTHS_INACTIVE` (default `6`). Match these to the discovery configuration.

```shell
aws lambda invoke --function-name <UNUSED_ACCOUNTS_FUNCTION> \
  --cli-binary-format raw-in-base64-out --payload '{}' plans-response.json
```

Inspect the response and review the generated plans under `s3://<bucket>/<plan-prefix>/`
before explicitly executing them:

```shell
aws lambda invoke --function-name <UNUSED_ACCOUNTS_FUNCTION> \
  --cli-binary-format raw-in-base64-out --payload '{"Execute": true}' execution-response.json
```

The Lambda rechecks current account data before deletion and moves plans to `done/`
or `skipped/`. Inspect the response summary for failed operations.

### Athena output files

Athena writes temporary query result files to:
```
s3://<bucket>/athena-output/
```
These files are intermediary Athena artifacts and are separate from the final exported JSON dataset.

## Discovery script output

The final work plan JSON dataset is uploaded to:
```
s3://<bucket>/<prefix>/unused-accounts-<n>.json
```

### Contents of work plan JSON should look like

```json
[
  {"user_id": "...", "identity": "...", "email": "...", "last_login": "2025-11-14T08:37:47Z"},
  ...
]
```
