# merge accounts - users with more than one account with same one login subject

Merge duplicate ActorUsers accounts that share the same One Login identity, while preserving LPAs and Viewer Codes.

### What the script does
This is a one-off migration script designed to safely merge duplicate user accounts in DynamoDB.

It follows a plan → review → execute workflow and uses S3 for persistence, allowing execution from different environments and safe resumption.

### What the script does:

For each duplicate identity group:

1. Identifies duplicate users by Identity
2. Selects a primary user (most recent login)
3. Collects all UserLpaActorMap mappings
4. Resolves duplicate mappings (same ActorId + LPA)
5. Repoints ViewerCodes where necessary
6. Moves canonical mappings to the primary user
7. Deletes:
   - duplicate mappings
   - secondary user accounts
8. Tracks progress using S3 checkpoints

### Prerequisites
    Docker installed
    aws-vault installed and configured
    access to the required AWS role
    MFA configured if required

## Requirements
boto3

```
pip install -r ./requirements.txt
```

## Build
Run from the folder containing dockerfile

```
docker build -t duplicate-identity-merge .
```

### Workflow

## 1. Generate merge plan - Dry run:
```
aws-vault exec identity -- sh -c '
docker run --rm \
  -e AWS_ACCESS_KEY_ID \
  -e AWS_SECRET_ACCESS_KEY \
  -e AWS_SESSION_TOKEN \
  -e AWS_DEFAULT_REGION=eu-west-1 \
  duplicate-identity-merge \
  --environment demo \
  --limit 1 '
```

This will:
    - scan for duplicate identities
    - generate a merge plan
    - print them to console
    - store it to S3

Output:
    Printed plan in terminal
    Saved to S3:
    ```
    s3://duplicate-accounts-s3/merge-plans/demo/merge_plan_YYYYMMDD_HHMMSS.json
    ```

## 2. Review Merge Plan:

Before executing:
    Open the plan in S3
    Validate:
        correct primary user selected
        mappings are correct
        viewer code moves are expected

## 3. Execute Merhe Plan:

```
aws-vault exec identity -- sh -c '
docker run --rm -it \
  -e AWS_ACCESS_KEY_ID \
  -e AWS_SECRET_ACCESS_KEY \
  -e AWS_SESSION_TOKEN \
  -e AWS_DEFAULT_REGION=eu-west-1 \
  duplicate-identity-merge \
  --environment demo \
  --execute \
  --plan-key <s3 key>'

```
eg: --plan-key merge-plans/demo/merge_plan_20260415_123456.json

This will:
    apply all changes to DynamoDB
    update viewer codes
    delete duplicates
    write progress checkpoints to S3

## To resume safely interrupted runs:

The script stores progress in S3 checkpoints and will resume automatically.
No change to command — just rerun.

Script will:
    load checkpoint from S3
    skip completed identities
    continue remaining work


## List available plans:
```
aws-vault exec identity -- sh -c '
docker run --rm \
  -e AWS_ACCESS_KEY_ID \
  -e AWS_SECRET_ACCESS_KEY \
  -e AWS_SESSION_TOKEN \
  -e AWS_DEFAULT_REGION=eu-west-1 \
  duplicate-identity-merge \
  --environment demo \
  --list-plans'

```

## Batching
add --offset so batches can be run like:

--limit 100 --offset 0
--limit 100 --offset 100

## Recommended
1. Run with --limit 5
2. Review plan in S3
3. Execute
4. Validate results
5. Increase batch size gradually
