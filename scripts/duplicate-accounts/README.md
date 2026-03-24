# merge accounts - users with more than one account with same one login subject

Combine accounts that have the same one login subject in a way that retains the added LPAs and active share codes.

### What the script does
This is a one-off migration script for merging duplicate ActorUsers records based on shared One Login identity.

The script:

1. finds duplicate accounts by Identity
2. selects the most recently logged-in account as primary
3. merges UserLpaActorMap rows
4. repoints ViewerCodes where required
5. deletes duplicate mappings and secondary accounts
6. supports dry-run and execute modes
7. Setup for containerising and running the script

### Prerequisites
    Docker installed
    aws-vault installed and configured
    access to the required AWS role
    MFA configured if required

## Requirements
boto3

## Build
Run from the folder containing dockerfile

```
docker build -t duplicate-identity-merge .
```

Dry run:
```
aws-vault exec identity -- sh -c '
docker run --rm \
  -e AWS_ACCESS_KEY_ID \
  -e AWS_SECRET_ACCESS_KEY \
  -e AWS_SESSION_TOKEN \
  -e AWS_DEFAULT_REGION=eu-west-1 \
  duplicate-identity-merge \
  --environment demo \
  --limit 1
```

Execute:
```
aws-vault exec identity -- sh -c '
docker run --rm -it \
  -e AWS_ACCESS_KEY_ID \
  -e AWS_SECRET_ACCESS_KEY \
  -e AWS_SESSION_TOKEN \
  -e AWS_DEFAULT_REGION=eu-west-1 \
  duplicate-identity-merge \
  --environment demo \
  --limit 1 \
  --execute
```

add --offset so batches can be run like:

--limit 100 --offset 0
--limit 100 --offset 100


Notes:
    - Run dry-run first and review the merge plan output
    - Use --limit to process small batches first
    - The script to be treated as plan → review → execute

Optional: save plan file to host

Mount a host folder

mkdir -p output

aws-vault exec identity -- sh -c '
docker run --rm \
  -e AWS_ACCESS_KEY_ID \
  -e AWS_SECRET_ACCESS_KEY \
  -e AWS_SESSION_TOKEN \
  -e AWS_DEFAULT_REGION=eu-west-1 \
  -v "$(pwd)/output:/app/output" \
  duplicate-identity-merge \
  --environment demo \
  --limit 1
