# Seed Unused Accounts

Generates test data for the unused accounts process (see [`scripts/unused_accounts/README.md`](../unused_accounts/README.md)).

> [!WARNING]
> Only seed non-production environments. Seeded accounts are real rows; eligible accounts can be deleted if the unused accounts workflow runs and its deletion plans are executed.

It creates **10,000 account records** in `ActorUsers`. The data is randomized:
- Every account has a generated ID, an `example.org` email address, a `LastLogin`, and `Comment = "Seeded Account: Unused accounts bulk seeding"`.
- About **25%** have a `LastLogin` 6-12 months ago; the rest have one from the last 6 months. Timestamps are random and in RFC 3339 UTC format.
- About **50%** have one generated LPA row in `UserLpaActorMap`; the rest have no LPAs.
- About **75%** have an `Identity` (`urn:fdc:mock-one-login:2023:...`) and a corresponding `IDENTITY#<identity>` pin row in `ActorUsers`. The remaining accounts have a generated password instead of an identity.

These percentages are randomized, not guaranteed counts. Only accounts with an old-enough `LastLogin` and no LPAs qualify for discovery.

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

Or use `scripts/unused_accounts/Makefile` with `make seed` which builds and runs the seeder and creates the bucket.
