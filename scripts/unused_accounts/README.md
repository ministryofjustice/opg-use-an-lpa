# Delete unused accounts

Finds and deletes **unused accounts**: accounts that have **no associated LPAs** and have **not been logged into for 6 months** or have **never logged in**.

The workflow has the same structure as the old merge-duplicate-identities process:

1. Export DynamoDB to S3 and load it into Athena (`dynamodb_export.py`).
2. Run the Athena query and write work files to S3 (`discover_unused_accounts.py`).
3. Run the `unused-accounts` lambda with `Execute=false`. It re-checks every account in DynamoDB and writes one deletion plan per account for review.
4. Run the lambda with `Execute=true`. It re-checks each plan against current data, deletes the account if nothing has changed, and moves the plan to `done/` or `skipped/`.

`ActorUsers` also holds `IDENTITY#<identity>` pin rows, which the API writes to allow only one account per OneLogin identity. They are not accounts, so discovery excludes them and the lambda never plans them on their own. When an account is deleted, its pin row is deleted in the same transaction, as the API's `ActorUsers::delete()` does.

Athena does the expensive whole-table search over a point-in-time export. The lambda never scans the live tables. It only uses `GetItem` and `Query` for the accounts named in the work files.

## Athena query

Built by `build_unused_accounts_query()` in `discover_unused_accounts.py`. `dynamodb_export.py` uses the same function for its CSV report.

```sql
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
     OR substr(a.item.lastlogin.s, 1, 10) <= CAST(date_add('month', -<months>, current_date) AS varchar)  -- --months-inactive, default 6
  )
ORDER BY a.item.id.s
```

Notes:
- `LastLogin` is stored as an ISO 8601 string, with or without a time zone and fractional seconds. The query compares its date part as a string, because ISO dates sort correctly as text. The `actor_users` table therefore declares `LastLogin` as a string (`tables/actor_users.ddl`).
- "No LPAs" is written as a `LEFT JOIN … IS NULL` because Athena/Trino rejects the equivalent correlated `NOT EXISTS` subquery.
- The lambda applies exactly the same rules (`is_inactive` in `lambda-functions/unused-accounts/delete_unused_accounts.py`): calendar months, and on-or-before the threshold date.

## Work files

`discover_unused_accounts.py` writes up to 100 accounts per file, replacing any work files from a previous run:

```
s3://<bucket>/todo/unused-accounts-1.json
s3://<bucket>/todo/unused-accounts-2.json
...
```

```json
[
  {
    "user_id": "0001a0aa-5102-4c5f-b9bd-9e2c71a6fae9",
    "identity": "urn:fdc:one-login:2023:...",
    "email": "someone@example.org",
    "last_login": "2025-12-28T03:46:14Z"
  }
]
```

`last_login` is `null` for accounts that never logged in.

## Deletion plans

With `Execute=false` the lambda writes `s3://<bucket>/plan/<md5 of account id>.json` for every account that is still unused:

```json
{
  "account_id": "0001a0aa-5102-4c5f-b9bd-9e2c71a6fae9",
  "identity": "urn:fdc:one-login:2023:...",
  "email": "someone@example.org",
  "last_login": "2025-12-28T03:46:14Z",
  "lpa_count": 0,
  "identity_pin": "IDENTITY#urn:fdc:one-login:2023:...",
  "reason": "No login for 6+ months and no associated LPAs"
}
```

`identity_pin` is the pin row to delete with the account. It is `null` if the account has no identity, or if another account has the same identity (checked with the `IdentityIndex` GSI), in which case the pin is kept.

Accounts that were deleted, logged in, or gained an LPA since the export are skipped and logged, as are any `IDENTITY#` ids.

With `Execute=true` each plan is rebuilt from current data and must match the stored plan. The account and its pin row are deleted in one `TransactWriteItems`, with a condition that `LastLogin` is unchanged, which protects against a login during execution. The plan is then moved:
- `done/plan/...`: account deleted
- `skipped/plan/...`: account no longer eligible (logged in, gained an LPA, or already gone)
- left in `plan/`: an error occurred, so the plan is retried on the next run

## Lambda configuration

| Variable | Default | |
|---|---|---|
| `BUCKET` | (required) | Bucket for work files and plans |
| `ENVIRONMENT_NAME` | `""` | DynamoDB table prefix |
| `WORK_FILE_PREFIX` | `todo` | |
| `PLAN_FILE_PREFIX` | `plan` | |
| `MONTHS_INACTIVE` | `6` | Must match `--months-inactive` used for discovery |

Invoke with `{"Execute": false}` to plan and `{"Execute": true}` to delete. Only a JSON boolean `true` deletes; anything else, including the string `"True"`, just generates plans. `Execute` is the only event field. The response includes a summary:

```json
{"statusCode": 200, "message": "...", "summary": {"work_items": 10000, "plans_saved": 10000, "skipped": 0, "duration_seconds": 191.0}}
{"statusCode": 200, "message": "...", "summary": {"deleted": 10000, "skipped": 0, "failed": 0, "duration_seconds": 291.8}}
```

The lambda timeout is 900s. Locally, 10,000 plans took about 3 minutes to create and 5 minutes to execute. If execute times out, invoke it again: completed plans have already been moved out of `plan/`, so it continues with the rest.

# Running in AWS

From CloudShell:

```shell
$ git clone https://github.com/ministryofjustice/opg-use-an-lpa.git
$ cd opg-use-an-lpa/scripts/unused_accounts
$ pip install boto3

# 1. Export DynamoDB and (re)create the Athena tables
$ python dynamodb_export.py --environment <ENVIRONMENT>

# 2. Find unused accounts and write work files
$ python discover_unused_accounts.py -d ual -b use-a-lpa-dynamodb-exports-<ACCOUNT_NAME>
```

Then invoke the `<ENVIRONMENT>-unused-accounts` lambda with `{"Execute": false}`, review `plan/`, and invoke it with `{"Execute": true}`. The lambda is only deployed where `unused_accounts_lambda` is enabled (`default` (dev) and `production`), and has no trigger.

```shell
$ aws lambda invoke --function-name <ENVIRONMENT>-unused-accounts \
    --cli-binary-format raw-in-base64-out --payload '{"Execute": false}' /tmp/response.json
```

The default CLI read timeout is 60s, so for long runs add `--cli-read-timeout 0`, or check progress in the lambda's CloudWatch logs.

### Script options

```shell
$ python discover_unused_accounts.py --help
  -d D                  The Athena database (or Trino schema) to query against. default: ual
  -b B                  The bucket to which to write the work files.
  -p P                  Bucket prefix for work files. default: todo
  --months-inactive N   Accounts not logged into for this many months are unused. default: 6
  --engine {athena,trino}
                        Query engine. Use 'trino' for local testing. default: athena
  --trino-url URL       Trino URL when --engine trino. default: http://localhost:8085
  --trino-catalog NAME  Trino catalog when --engine trino. default: hive
  --s3-endpoint URL     Override the S3 endpoint (e.g. LocalStack). Defaults to $AWS_ENDPOINT_S3 if set.
```

Athena writes intermediate query results to `s3://<bucket>/athena-output/`.

# Local testing

LocalStack Community supports neither DynamoDB export to S3 nor Athena. Locally:

- `local_export_to_trino.py` stands in for `dynamodb_export.py`. It copies the LocalStack DynamoDB tables into LocalStack S3 in the same gzipped DynamoDB-JSON format a real export produces, and creates the `ual` tables in a local Trino container.
- Trino stands in for Athena. Athena engine v3 is built on Trino, so `discover_unused_accounts.py --engine trino` runs the **same SQL**. Trino runs in the `trino` service in `docker-compose.dependencies.yml`, under the `unused-accounts` profile, so it isn't started by `make up`.

Use `test_unused_accounts.sh` in this directory (requires `uv`). It finds the other scripts, compose files and `Makefile` relative to its own location, so it can be run from any directory. From the repository root:

```shell
make up
scripts/unused_accounts/test_unused_accounts.sh seed       # seed 10k unused accounts (scripts/seed_unused_accounts)
scripts/unused_accounts/test_unused_accounts.sh discover   # export -> Trino -> query -> todo/ work files
scripts/unused_accounts/test_unused_accounts.sh plan       # lambda Execute=false
scripts/unused_accounts/test_unused_accounts.sh review     # inspect sample plans
scripts/unused_accounts/test_unused_accounts.sh execute    # lambda Execute=true
scripts/unused_accounts/test_unused_accounts.sh verify
```

The local tables have no prefix, and the local lambda runs under the Runtime Interface Emulator on port 9008, not in LocalStack. See [`LOCAL_TESTING_GUIDE.md`](../../LOCAL_TESTING_GUIDE.md) for the manual steps and troubleshooting.

# Legacy: `delete_unused_accounts.py`
The earlier CSV-based script, which deletes accounts listed in `results/UnusedAccounts-*.csv`, is still here with its tests (`test_delete_unused_accounts.py`). It is superseded by the work file → plan → execute lambda workflow above. **It does not currently work:** it expects `UserId` and `Email` CSV columns, but `dynamodb_export.py` now writes `user_id, identity, email, last_login`. It also deletes without re-checking `LastLogin`, and does not delete `IDENTITY#` pin rows.
