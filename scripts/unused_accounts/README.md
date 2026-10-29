# Delete unused accounts
These scripts find unused accounts in DynamoDB exports queried through Athena (or Trino for local testing). Discovery writes account work files to S3; the Lambda turns those into deletion plans for review.

An account is eligible when it has no associated LPAs and either has never logged in or its last login was at least the configured number of months ago (six by default). `IDENTITY#<identity>` pinning rows are excluded; they are not accounts.

Nothing is deleted during discovery or plan generation. Accounts are deleted only when the Lambda is invoked with `{"Execute": true}`, after reviewing the plans. The Lambda checks each account again before deleting it.

## Objective
Find eligible accounts in the DynamoDB exports and write S3 work files for the Lambda.

## High Level Flow

The two scripts and the unused-accounts Lambda perform the following steps:

1. Export `ActorUsers` and `UserLpaActorMap` from DynamoDB to S3, then create the Athena database `ual` tables over those exports
2. Execute an Athena query against the `ActorUsers` dataset
3. Identify unused accounts
4. Write JSON work files, with up to 100 accounts per file
5. Upload the work files to S3
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

The query returns one row per eligible account, with these columns:

| user_id | identity | email | last_login |
|---|---|---|---|
| `d3e6e244-9ab8-4dcf-9f79-a77c3f4b1846` | `urn:fdc:mock-one-login:2023:RlhZSN0Pupbb6yUHKvBJeTbUMiOvM3hPRQ7QZzu/Mio=` | `person@example.org` | `2025-11-14T08:37:47Z` |

`identity` and `last_login` can be `NULL` when the account has no identity or has never logged in.


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
usage: discover_unused_accounts.py [-h] [-d D] -b B [-p P] [--months-inactive MONTHS_INACTIVE] [--engine {athena,trino}] [--trino-url TRINO_URL] [--trino-catalog TRINO_CATALOG] [--s3-endpoint S3_ENDPOINT] [--verify]

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
  --verify              Check that no unused accounts remain, without changing work files. Exit nonzero if any remain.

$ uv run local_export_to_trino.py -h
usage: local_export_to_trino.py [-h] -b B [-d D] [--table-prefix TABLE_PREFIX] [--endpoint ENDPOINT] [--trino-url TRINO_URL] [--trino-catalog TRINO_CATALOG]

LOCAL ONLY: export LocalStack DynamoDB tables to S3 and load them into the local Trino

options:
  -h, --help            show this help message and exit
  -b B                  Bucket to write the export to
  -d D                  Trino schema (Athena database equivalent). default: ual
  --table-prefix TABLE_PREFIX
                        DynamoDB table prefix (empty for none)
  --endpoint ENDPOINT   LocalStack endpoint as seen from this machine. default: http://localhost:4566
  --trino-url TRINO_URL
                        Trino URL. default: http://localhost:8085
  --trino-catalog TRINO_CATALOG
                        Trino catalog. default: hive
```

Use `--environment` with `development`, `preproduction` or `production`; any other value selects the development
AWS account. For production, the discovery command would use `-d ual -b use-a-lpa-dynamodb-exports-production`.

# Running the scripts

## Python dependencies
Use uv to install the Python version and dependencies declared in `pyproject.toml`.
Both scripts use the AWS credentials already available in their execution environment;
they do not assume roles. Locally, run them through `aws-vault exec <PROFILE> --`.

In cloudshell you'll need to pull the project and install the dependencies yourself
and you will need to have the correct AWS role or credentials for it to succeed.

```shell
$ git clone https://github.com/ministryofjustice/opg-use-an-lpa.git
$ cd opg-use-an-lpa/scripts/unused_accounts
$ pip install boto3
$ python dynamodb_export.py --environment <ENVIRONMENT>
$ python discover_unused_accounts.py -d ual -b use-a-lpa-dynamodb-exports-<ENVIRONMENT>
```
Replace `<ENVIRONMENT>` with `development`, `preproduction` or `production`. The exporter waits
for the exact DynamoDB exports it starts before rebuilding Athena's `ual` database. `--check_exports`
instead waits for each table's most recently started existing export and does not rebuild Athena.
Failed or missing exports stop the script.

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

The discovery script writes its Athena query results to:
```
s3://<bucket>/athena-output/
```
These are temporary Athena artifacts, separate from the discovery work files. The export script's Athena DDL queries instead use the bucket root as their output location.

## Discovery script output

Discovery uploads work files (up to 100 accounts per file) to:
```
s3://<bucket>/<prefix>/unused-accounts-<n>.json
```

### Work file format

```json
[
  {
    "user_id": "d3e6e244-9ab8-4dcf-9f79-a77c3f4b1846",
    "identity": "urn:fdc:mock-one-login:2023:RlhZSN0Pupbb6yUHKvBJeTbUMiOvM3hPRQ7QZzu/Mio=",
    "email": "person@example.org",
    "last_login": "2025-11-14T08:37:47Z"
  }
]
```

`identity` and `last_login` are `null` when the source account has no identity or has never logged in. These files are inputs to plan generation; the Lambda writes separate deletion plan files under `s3://<bucket>/<plan-prefix>/`.

## Development

The Makefile targets run the local workflow against LocalStack and Trino. Start the
repository's Docker dependencies first, then run targets from the repository root
(you can omit the '-C' argument if you are in the unused_accounts folder):

```shell
make -C scripts/unused_accounts seed
make -C scripts/unused_accounts export
make -C scripts/unused_accounts discover
make -C scripts/unused_accounts invoke
```

`seed` adds test accounts to LocalStack. `export` starts Trino and exports the local
DynamoDB tables to S3 in a format Trino can query. `discover` writes work files, and
`invoke` calls the local Lambda to create deletion plans. Review those plans before
running `make -C scripts/unused_accounts execute`, which deletes still-eligible
accounts from LocalStack.

**Other targets:**

`verify` refreshes the local export and checks that no eligible
accounts remain; it exits with an error if any are found.

`end-to-end` runs
`seed`, `export`, `discover`, `invoke`, `execute` and `verify` in order, so it
checking a full run of the process.

The defaults use LocalStack at `http://localhost:4566`, Trino at
`http://localhost:8085`, an empty DynamoDB table prefix, and the `unused-accounts-bucket`
S3 bucket. Override them when needed, for example:

```shell
make -C scripts/unused_accounts seed TABLE_PREFIX=demo
make -C scripts/unused_accounts discover BUCKET=my-bucket
```
