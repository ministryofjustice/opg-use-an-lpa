# Testing the Unused Accounts Lambda Locally

This guide tests the unused accounts process end-to-end with Docker Compose, LocalStack and a local Trino container.
For how the process works in AWS, see [`scripts/unused_accounts/README.md`](scripts/unused_accounts/README.md).

The quickest way is the test script, which runs every step below . The manual commands are listed afterwards
for debugging or running a single step by hand.

## Prerequisites

- Docker with Docker Compose v2 (`docker compose`)
- AWS CLI (talks to LocalStack, not real AWS)
- `jq`
- Go (builds the seeder)
- [`uv`](https://docs.astral.sh/uv/) (runs the Python scripts: `brew install uv`)

## How the local setup differs from AWS

| | AWS | Local |
|---|---|---|
| DynamoDB and S3 | Real services | LocalStack on `http://localhost:4566` |
| DynamoDB tables | Prefixed with the environment, e.g. `demo-ActorUsers` | **No prefix** (`ActorUsers`, `UserLpaActorMap`), created by `localstack/localstack-init.sh` |
| Export + Athena | `dynamodb_export.py`, Athena | `local_export_to_trino.py`, Trino on `http://localhost:8085` (same SQL) |
| Lambda | `<environment>-unused-accounts` | Lambda Runtime Interface Emulator container on `http://localhost:9008`. It is **not** deployed into LocalStack, so `aws lambda invoke --endpoint-url http://localhost:4566` does not work |
| Bucket | `use-a-lpa-dynamodb-exports-<account>` | `unused-accounts-bucket`, created by the `seed` step |

## Quick start: the test script

From the repository root (the script works from any directory):

```bash
make down && make up                                          # LocalStack has a healthcheck, so dependants wait for it
scripts/unused_accounts/test_unused_accounts.sh seed          # bucket + 10k unused accounts
scripts/unused_accounts/test_unused_accounts.sh discover      # starts Trino, exports DynamoDB -> S3 -> Trino, writes todo/ work files
scripts/unused_accounts/test_unused_accounts.sh plan          # lambda Execute=false -> plan/
scripts/unused_accounts/test_unused_accounts.sh review 5      # total plan count + first 5 plans
scripts/unused_accounts/test_unused_accounts.sh execute       # lambda Execute=true (asks for confirmation)
scripts/unused_accounts/test_unused_accounts.sh verify        # checks accounts, IDENTITY# pins and plan folders
make down                                                     # also stops Trino
```

Run `scripts/unused_accounts/test_unused_accounts.sh help` for all commands.

Expected `verify` output after a clean run:

```
0 seeded unused accounts remaining (should be 0)
0 seeded accounts kept because they logged in within 6 months (expected)
19 other rows kept (standard test users and IDENTITY# pins - expected)
0 orphaned IDENTITY# pins with no account (should be 0)
0 accounts whose IDENTITY# pin is missing (should be 0)
...
✓ Deletion successful!
```

The 19 remaining rows are the standard api-seeding test users and their `IDENTITY#` pins. They are recent or have LPAs,
so they are correctly kept. The `done/` count includes plans from previous runs, because each run's plans are named
after different account ids.

## Manual steps

All commands below are run from the repository root and use LocalStack:

```bash
export AWS_ACCESS_KEY_ID=test AWS_SECRET_ACCESS_KEY=test AWS_DEFAULT_REGION=eu-west-1
unset AWS_PROFILE AWS_VAULT AWS_SESSION_TOKEN
EP=http://localhost:4566
```

### 1. Start the environment

```bash
make down && make up
curl -s $EP/_localstack/health | jq .
```

### 2. Create the bucket and seed accounts

```bash
aws s3 mb s3://unused-accounts-bucket --endpoint-url $EP

(cd scripts/seed_unused_accounts && go build -o seed_unused_accounts main.go \
  && ./seed_unused_accounts -p "" -e $EP)
```

`-p ""` is required: the seeder's default prefix is `demo`, but the local tables have no prefix.
The seeder creates 10,000 accounts, each with an `IDENTITY#<identity>` pin row, so `ActorUsers` grows by 20,000 rows.
See [`scripts/seed_unused_accounts/README.md`](scripts/seed_unused_accounts/README.md).

### 3. Discover unused accounts (local Athena)

```bash
docker compose -f docker-compose.yml -f docker-compose.dependencies.yml \
  --profile unused-accounts up -d --no-deps --wait trino

cd scripts/unused_accounts
uv run --no-project --with boto3 python local_export_to_trino.py \
  -b unused-accounts-bucket --table-prefix "" --endpoint $EP --trino-url http://localhost:8085
uv run --no-project --with boto3 python discover_unused_accounts.py --engine trino \
  --trino-url http://localhost:8085 -b unused-accounts-bucket -p todo --s3-endpoint $EP
cd ../..

aws s3 ls s3://unused-accounts-bucket/todo/ --endpoint-url $EP | wc -l    # 100 work files for 10k accounts
```

The lambda only processes accounts listed in the `todo/` work files. It never scans the tables, so `plan` does nothing
if this step is skipped.

### 4. Generate plans (`Execute=false`)

```bash
curl -s -XPOST http://localhost:9008/2015-03-31/functions/function/invocations \
  -d '{"Execute": false}' | jq .
```

```json
{
  "statusCode": 200,
  "message": "Generation of all deletion plans completed successfully",
  "summary": {"work_items": 10000, "plans_saved": 10000, "skipped": 0, "duration_seconds": 191.0}
}
```

The lambda re-checks every account with `GetItem` and a `UserIndex` query. It skips accounts that no longer exist, have
logged in, have gained an LPA, or are `IDENTITY#` pin rows.

### 5. Review plans

```bash
aws s3 ls s3://unused-accounts-bucket/plan/ --endpoint-url $EP | wc -l
KEY=$(aws s3api list-objects-v2 --bucket unused-accounts-bucket --prefix plan/ --max-items 1 \
  --endpoint-url $EP --query 'Contents[0].Key' --output text)
aws s3 cp s3://unused-accounts-bucket/$KEY - --endpoint-url $EP | jq .
```

```json
{
  "account_id": "56738feb-d8d9-4031-86a8-51eb1f2a7669",
  "identity": "urn:fdc:mock-one-login:2023:...",
  "email": "CappyGh@example.org",
  "last_login": "2026-02-04T22:43:19Z",
  "lpa_count": 0,
  "identity_pin": "IDENTITY#urn:fdc:mock-one-login:2023:...",
  "reason": "No login for 6+ months and no associated LPAs"
}
```

`identity_pin` is the pin row deleted with the account. It is `null` if the account has no identity, or if another
account shares the identity.

### 6. Execute (`Execute=true`)

```bash
curl -s -XPOST http://localhost:9008/2015-03-31/functions/function/invocations \
  -d '{"Execute": true}' | jq .
```

```json
{
  "statusCode": 200,
  "message": "Execution of all deletion plans completed successfully",
  "summary": {"deleted": 10000, "skipped": 0, "failed": 0, "duration_seconds": 291.8}
}
```

For each plan, the lambda rebuilds the plan from current data and skips the account if anything has changed. Otherwise
it deletes the account and its `IDENTITY#` pin in one `TransactWriteItems`, conditioned on `LastLogin` being unchanged.
Plans are then moved to:
- `done/plan/`: deleted
- `skipped/plan/`: no longer eligible
- left in `plan/`: an error occurred; retried on the next run

`curl` waits until the lambda finishes. If the call is interrupted, the lambda keeps running in the container.

### 7. Verify

```bash
aws dynamodb scan --table-name ActorUsers --select COUNT --endpoint-url $EP | jq .Count     # 19 after a clean run
for p in plan done skipped; do
  echo "$p: $(aws s3 ls s3://unused-accounts-bucket/$p/ --recursive --endpoint-url $EP | wc -l)"
done
```

The test script's `verify` command also checks that no seeded unused accounts remain, and that every account has its
`IDENTITY#` pin and no pin is orphaned.

## Configuration

The local lambda is configured in `docker-compose.yml` (service `unused-accounts-lambda`):

| Variable | Local value | Notes |
|---|---|---|
| `BUCKET` | `unused-accounts-bucket` | |
| `ENVIRONMENT_NAME` | not set | Table prefix. Unset means no prefix, which matches the local tables |
| `WORK_FILE_PREFIX` | `todo` | |
| `PLAN_FILE_PREFIX` | `plan` | |
| `MONTHS_INACTIVE` | `6` | Must match `--months-inactive` passed to `discover_unused_accounts.py` |
| `AWS_LAMBDA_FUNCTION_TIMEOUT` | `900` | Same as Terraform. The emulator default is 300s |

The only event field is `Execute` (`true`/`false`, or the strings `"True"`/`"False"`). To change the inactivity period,
set `MONTHS_INACTIVE` in a `docker-compose.override.yml` and pass the same value to the discover script:

```yaml
services:
  unused-accounts-lambda:
    environment:
      MONTHS_INACTIVE: "3"
```

Then run `make down && make up`.

## Debugging

```bash
make logs unused-accounts-lambda                      # lambda logs
make rebuild unused-accounts-lambda && make up        # rebuild after changing the lambda code
make ps                                               # service status
open http://localhost:8085                            # Trino UI
aws s3 ls s3://unused-accounts-bucket/ --recursive --endpoint-url http://localhost:4566 | head
```

Lambda unit tests:

```bash
cd lambda-functions/unused-accounts && uv run --group test pytest
```

## Troubleshooting

| Problem | Check |
|---|---|
| `Could not reach lambda at http://localhost:9008/...` | `make ps`; the `unused-accounts-lambda` container must be running. `lsof -i :9008` |
| `connection refused` on port 4566 | LocalStack is not up yet: `curl http://localhost:4566/_localstack/health` |
| `security token` or credential errors | Real AWS credentials in your shell (aws-vault, `AWS_PROFILE`). Unset them and use dummy `test` credentials |
| `ResourceNotFoundException` for `demo-ActorUsers` | Seeder run without `-p ""`, or lambda given `ENVIRONMENT_NAME`. Local tables have no prefix |
| `plan` returns `work_items: 0` | Run `discover` first. It writes the `todo/` work files the lambda reads |
| `discover` finds 0 accounts | Seed first. Seeded accounts have `LastLogin` 6-12 months ago |
| Trino not starting | `docker compose -f docker-compose.yml -f docker-compose.dependencies.yml --profile unused-accounts logs trino` |
| Plans left in `plan/` after `execute` | Errors during execution: check `make logs unused-accounts-lambda`, then run `execute` again |

## Cleanup

```bash
make down       # stop and remove all containers, including Trino
make destroy    # also remove volumes and images
```


