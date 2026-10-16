# Seed Unused Accounts

Generates test data for the unused accounts process (see [`scripts/unused_accounts/README.md`](../unused_accounts/README.md)).
It creates **10,000 accounts** in `ActorUsers`, each with:
- **no LPAs** (nothing in `UserLpaActorMap`)
- **`LastLogin` 6-12 months ago** (random, RFC 3339 UTC, e.g. `2026-02-04T22:43:19Z`)
- an `Identity` (`urn:fdc:mock-one-login:2023:...`) and an **`IDENTITY#<identity>` pin row**, as the API writes for every account. The table therefore grows by 20,000 rows.
- `Comment = "Seeded Account: Unused accounts bulk seeding"`, so seeded accounts can be told apart from others

All seeded accounts should be found by discovery and deleted by the lambda, together with their pin rows.

## Setup (CloudShell)

```shell
sudo yum install -y golang
sudo mkdir /go
sudo chown $(whoami):$(whoami) /go
export GOPATH=/go
```

## Build

```shell
cd scripts/seed_unused_accounts
go build -o seed_unused_accounts main.go
```

The binary is git-ignored.

## Usage

```shell
./seed_unused_accounts -p <table-prefix> -e <endpoint-url>
```

- `-p` (default: `demo`): DynamoDB table prefix. Tables are named `<prefix>-ActorUsers`. Pass `-p ""` for no prefix.
- `-e` (optional): AWS endpoint URL override, e.g. LocalStack

### Local (LocalStack)

The local tables have **no prefix**, so pass `-p ""`:

```shell
AWS_ACCESS_KEY_ID=test AWS_SECRET_ACCESS_KEY=test AWS_REGION=eu-west-1 \
  ./seed_unused_accounts -p "" -e http://localhost:4566
```

Or use `scripts/unused_accounts/test_unused_accounts.sh seed`, which builds and runs the seeder and creates the bucket.

### AWS

Only seed non-production environments. Seeded accounts are real rows, and are deleted when the unused accounts lambda runs in that environment.

