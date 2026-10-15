#!/bin/bash
# Quick test script for unused accounts lambda
# Usage: scripts/unused_accounts/test_unused_accounts.sh [seed|discover|plan|review|execute|verify|logs|cleanup]
# Can be run from any directory - all paths are resolved relative to this script.

set -e

# Paths are relative to this script's location (scripts/unused_accounts/), not the caller's working directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
SEEDER_DIR="$(cd "$SCRIPT_DIR/../seed_unused_accounts" && pwd)"
SELF="$0"

REGION="eu-west-1"
ENDPOINT="http://localhost:4566"
BUCKET="unused-accounts-bucket"
LAMBDA="unused-accounts-lambda"
TABLE_PREFIX=""
# The lambda is NOT deployed into LocalStack; it runs in its own container under the
# AWS Lambda Runtime Interface Emulator (see docker-compose.yml, port 9008).
LAMBDA_INVOKE_URL="${LAMBDA_INVOKE_URL:-http://localhost:9008/2015-03-31/functions/function/invocations}"
TRINO_URL="${TRINO_URL:-http://localhost:8085}"
COMPOSE="docker compose --project-directory $REPO_ROOT -f $REPO_ROOT/docker-compose.yml -f $REPO_ROOT/docker-compose.dependencies.yml"
MAKE="make -C $REPO_ROOT"

# Invoke the lambda via the Runtime Interface Emulator
# Usage: invoke_lambda <json-payload> <output-file>
invoke_lambda() {
    local payload="$1"
    local outfile="$2"
    local http_code
    http_code=$(curl -s -o "$outfile" -w "%{http_code}" -XPOST "$LAMBDA_INVOKE_URL" -d "$payload") || {
        echo "✗ Could not reach lambda at $LAMBDA_INVOKE_URL"
        echo "  Is the $LAMBDA container running? Check 'make ps' / 'make logs $LAMBDA'"
        exit 1
    }
    if [ "$http_code" != "200" ] || grep -q '"errorMessage"' "$outfile"; then
        echo "✗ Lambda invocation failed (HTTP $http_code):"
        cat "$outfile"; echo
        exit 1
    fi
}

# Check if LocalStack is healthy
check_localstack() {
    echo "Checking LocalStack health..."
    for i in {1..30}; do
        if curl -s http://localhost:4566/_localstack/health > /dev/null 2>&1; then
            echo "✓ LocalStack is ready"
            return 0
        fi
        echo "  Waiting for LocalStack... ($i/30)"
        sleep 2
    done
    echo "✗ LocalStack failed to start after 60 seconds"
    echo "  Make sure 'make up' completed successfully and LocalStack is running"
    exit 1
}

# Wrapped in { ...; exit; } so bash parses the whole block before running it.
# This makes it safe to edit this file while a long-running command (e.g. execute) is in progress.
{
case "${1:-help}" in
  seed)
    echo "=== Seeding test data ==="

    check_localstack

    # Clear ALL AWS-related environment variables to prevent credential conflicts
    unset AWS_VAULT
    unset AWS_PROFILE
    unset AWS_ROLE_ARN
    unset AWS_ROLE_SESSION_NAME
    unset AWS_WEB_IDENTITY_TOKEN_FILE
    unset AWS_VAULT_SKIP_VERIFY
    unset AWS_VAULT_ASSUME_ROLE_TTL
    unset AWS_DATA_PATH
    unset BOTO_CONFIG

    # Set dummy AWS credentials explicitly for LocalStack
    # Do NOT set AWS_SESSION_TOKEN - it causes validation errors in LocalStack
    export AWS_ACCESS_KEY_ID="test"
    export AWS_SECRET_ACCESS_KEY="test"
    export AWS_DEFAULT_REGION="eu-west-1"
    unset AWS_SESSION_TOKEN

    echo ""
    echo "Step 0: Waiting for the DynamoDB tables (created by the api-seeding container on 'make up')..."
    # service-api/docker/seeding/start.sh creates the tables; this avoids racing it straight after 'make up'
    for TABLE in ActorUsers UserLpaActorMap; do
      TABLE_NAME="${TABLE_PREFIX:+${TABLE_PREFIX}-}$TABLE"
      if ! aws dynamodb wait table-exists --table-name "$TABLE_NAME" --region $REGION --endpoint-url $ENDPOINT; then
        echo "✗ Table $TABLE_NAME not found. Is api-seeding running? Check 'make logs api-seeding'"
        exit 1
      fi
    done
    echo "✓ Tables ready"

    echo ""
    echo "Step 1: Creating S3 bucket..."
    aws s3 mb s3://$BUCKET --region $REGION --endpoint-url $ENDPOINT || true
    echo "✓ Bucket ready"

    echo ""
    echo "Step 2: Building seeding script..."
    (cd "$SEEDER_DIR" && go build -o seed_unused_accounts main.go)
    echo "✓ Build complete"

    echo ""
    echo "Step 3: Running seeder (this may take a minute)..."
    # Quote args: an empty, unquoted $TABLE_PREFIX would make "-e" be consumed as the prefix value
    "$SEEDER_DIR/seed_unused_accounts" -p "$TABLE_PREFIX" -e "$ENDPOINT"
    echo "✓ Seeding complete"

    echo ""
    echo "Step 4: Verifying seed data..."
    TABLE_NAME="${TABLE_PREFIX:+${TABLE_PREFIX}-}ActorUsers"
    COUNT=$(aws dynamodb scan --table-name $TABLE_NAME \
      --select COUNT --region $REGION --endpoint-url $ENDPOINT | jq '.Count')
    echo "✓ $COUNT rows in ActorUsers (accounts + IDENTITY# pins)"
    ;;

  discover)
    echo "=== Discovering unused accounts (local Athena via Trino) ==="

    check_localstack

    # Clear ALL AWS-related environment variables to prevent credential conflicts
    unset AWS_VAULT AWS_PROFILE AWS_ROLE_ARN AWS_ROLE_SESSION_NAME AWS_WEB_IDENTITY_TOKEN_FILE
    unset AWS_VAULT_SKIP_VERIFY AWS_VAULT_ASSUME_ROLE_TTL AWS_DATA_PATH BOTO_CONFIG AWS_SESSION_TOKEN
    export AWS_ACCESS_KEY_ID="test"
    export AWS_SECRET_ACCESS_KEY="test"
    export AWS_DEFAULT_REGION="eu-west-1"

    if ! command -v uv > /dev/null 2>&1; then
      echo "✗ 'uv' is required to run the Python scripts (brew install uv)"
      exit 1
    fi

    echo ""
    echo "Step 1: Starting Trino (local stand-in for Athena)..."
    $COMPOSE --profile unused-accounts up -d --no-deps --wait trino
    echo "✓ Trino ready at $TRINO_URL"

    echo ""
    echo "Step 2: Exporting DynamoDB tables to S3 and loading them into Trino..."
    # local stand-in for: scripts/unused_accounts/dynamodb_export.py (DynamoDB export + Athena tables)
    (cd "$SCRIPT_DIR" && uv run --quiet --no-project --with boto3 \
      python local_export_to_trino.py -b "$BUCKET" --table-prefix "$TABLE_PREFIX" \
        --endpoint "$ENDPOINT" --trino-url "$TRINO_URL")

    echo ""
    echo "Step 3: Running the unused accounts query and writing work files..."
    # same script and SQL that runs against Athena in AWS, pointed at the local Trino
    (cd "$SCRIPT_DIR" && uv run --quiet --no-project --with boto3 \
      python discover_unused_accounts.py --engine trino --trino-url "$TRINO_URL" \
        -b "$BUCKET" -p todo --s3-endpoint "$ENDPOINT")

    WORK_FILES=$(aws s3 ls s3://$BUCKET/todo/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive | wc -l | tr -d ' ')
    echo ""
    echo "✓ $WORK_FILES work file(s) in s3://$BUCKET/todo/"
    ;;

  plan)
    echo "=== Generating deletion plans ==="

    # Clear ALL AWS-related environment variables to prevent credential conflicts
    unset AWS_VAULT
    unset AWS_PROFILE
    unset AWS_ROLE_ARN
    unset AWS_ROLE_SESSION_NAME
    unset AWS_WEB_IDENTITY_TOKEN_FILE
    unset AWS_VAULT_SKIP_VERIFY
    unset AWS_VAULT_ASSUME_ROLE_TTL
    unset AWS_DATA_PATH
    unset BOTO_CONFIG

    # Set dummy AWS credentials explicitly for LocalStack
    # Do NOT set AWS_SESSION_TOKEN - it causes validation errors in LocalStack
    export AWS_ACCESS_KEY_ID="test"
    export AWS_SECRET_ACCESS_KEY="test"
    export AWS_DEFAULT_REGION="eu-west-1"
    unset AWS_SESSION_TOKEN

    WORK_FILES=$(aws s3 ls s3://$BUCKET/todo/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive 2>/dev/null | wc -l | tr -d ' ')
    if [ "$WORK_FILES" -eq 0 ]; then
      echo "No work files found in s3://$BUCKET/todo/ - nothing to plan."
      echo "Run '$SELF discover' first."
      exit 1
    fi

    echo "Invoking lambda with Execute=false ($WORK_FILES work file(s))..."

    invoke_lambda '{"Execute": false}' /tmp/plan_response.json

    echo ""
    echo "Response:"
    cat /tmp/plan_response.json | jq .

    echo ""
    PLAN_COUNT=$(aws s3 ls s3://$BUCKET/plan/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive | wc -l)
    echo "✓ Plans generated: $PLAN_COUNT"
    ;;

  review)
    echo "=== Reviewing deletion plans ==="

    # Clear ALL AWS-related environment variables to prevent credential conflicts
    unset AWS_VAULT
    unset AWS_PROFILE
    unset AWS_ROLE_ARN
    unset AWS_ROLE_SESSION_NAME
    unset AWS_WEB_IDENTITY_TOKEN_FILE
    unset AWS_VAULT_SKIP_VERIFY
    unset AWS_VAULT_ASSUME_ROLE_TTL
    unset AWS_DATA_PATH
    unset BOTO_CONFIG

    # Set dummy AWS credentials explicitly for LocalStack
    # Do NOT set AWS_SESSION_TOKEN - it causes validation errors in LocalStack
    export AWS_ACCESS_KEY_ID="test"
    export AWS_SECRET_ACCESS_KEY="test"
    export AWS_DEFAULT_REGION="eu-west-1"
    unset AWS_SESSION_TOKEN

    # Number of sample plans to display (override with: $SELF review 10)
    SAMPLE_SIZE="${2:-3}"

    TOTAL=$(aws s3 ls s3://$BUCKET/plan/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive | wc -l | tr -d ' ')
    echo "Total plans in s3://$BUCKET/plan/: $TOTAL"
    echo "Showing the first $SAMPLE_SIZE:"

    # Fetch only the keys we need (avoids "Broken pipe" from piping a full listing into head)
    PLANS=$(aws s3api list-objects-v2 --bucket "$BUCKET" --prefix plan/ \
      --region $REGION --endpoint-url $ENDPOINT --max-items "$SAMPLE_SIZE" \
      --query 'Contents[].Key' --output text | tr '\t' '\n' | grep -v '^None$' | head -n "$SAMPLE_SIZE")

    COUNT=0
    for PLAN in $PLANS; do
      COUNT=$((COUNT + 1))
      echo ""
      echo "--- Plan $COUNT: $PLAN ---"
      aws s3 cp "s3://$BUCKET/$PLAN" - \
        --region $REGION --endpoint-url $ENDPOINT | jq '{id: .account_id, email: .email, last_login: .last_login, reason: .reason}'
    done
    ;;

  execute)
    echo "=== Executing deletion plans ==="

    # Clear ALL AWS-related environment variables to prevent credential conflicts
    unset AWS_VAULT
    unset AWS_PROFILE
    unset AWS_ROLE_ARN
    unset AWS_ROLE_SESSION_NAME
    unset AWS_WEB_IDENTITY_TOKEN_FILE
    unset AWS_VAULT_SKIP_VERIFY
    unset AWS_VAULT_ASSUME_ROLE_TTL
    unset AWS_DATA_PATH
    unset BOTO_CONFIG

    # Set dummy AWS credentials explicitly for LocalStack
    # Do NOT set AWS_SESSION_TOKEN - it causes validation errors in LocalStack
    export AWS_ACCESS_KEY_ID="test"
    export AWS_SECRET_ACCESS_KEY="test"
    export AWS_DEFAULT_REGION="eu-west-1"
    unset AWS_SESSION_TOKEN

    # Each plan in s3://$BUCKET/plan/ corresponds to one account to be deleted
    PLAN_COUNT=$(aws s3 ls s3://$BUCKET/plan/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive | wc -l | tr -d ' ')

    if [ "$PLAN_COUNT" -eq 0 ]; then
      echo "No deletion plans found in s3://$BUCKET/plan/ - nothing to delete."
      echo "Run '$SELF plan' first."
      exit 0
    fi

    echo "WARNING: This will delete $PLAN_COUNT account(s)!"
    read -p "Continue? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
      echo "Cancelled."
      exit 1
    fi

    echo "Invoking lambda with Execute=true..."
    invoke_lambda '{"Execute": true}' /tmp/exec_response.json

    echo ""
    echo "Response:"
    cat /tmp/exec_response.json | jq .
    ;;

   verify)
     echo "=== Verifying deletion ==="

     # Clear ALL AWS-related environment variables to prevent credential conflicts
     unset AWS_VAULT
     unset AWS_PROFILE
     unset AWS_ROLE_ARN
     unset AWS_ROLE_SESSION_NAME
     unset AWS_WEB_IDENTITY_TOKEN_FILE
     unset AWS_VAULT_SKIP_VERIFY
     unset AWS_VAULT_ASSUME_ROLE_TTL
      unset AWS_DATA_PATH
      unset BOTO_CONFIG

      # Set dummy AWS credentials explicitly for LocalStack
      # Do NOT set AWS_SESSION_TOKEN - it causes validation errors in LocalStack
      export AWS_ACCESS_KEY_ID="test"
      export AWS_SECRET_ACCESS_KEY="test"
      export AWS_DEFAULT_REGION="eu-west-1"
      unset AWS_SESSION_TOKEN

      echo ""
      echo "Remaining accounts in ActorUsers table:"
     TABLE_NAME="${TABLE_PREFIX:+${TABLE_PREFIX}-}ActorUsers"
     TOTAL_COUNT=$(aws dynamodb scan --table-name $TABLE_NAME \
       --select COUNT --region $REGION --endpoint-url $ENDPOINT | jq '.Count')
     # Only accounts created by scripts/seed_unused_accounts are expected to be deleted.
     # Other accounts (e.g. the standard api-seeding test users) are recent and/or have LPAs, so are kept.
     # Seeded accounts that are still eligible (no login for 6+ months) should all be gone.
     # A seeded account that has logged in since (LastLogin within 6 months) is correctly kept.
     # ISO-8601 strings compare correctly as strings. BSD date (macOS) first, then GNU date.
     THRESHOLD=$(date -u -v-6m +%Y-%m-%dT%H:%M:%SZ 2>/dev/null || date -u -d '6 months ago' +%Y-%m-%dT%H:%M:%SZ)
     SEEDED_COUNT=$(aws dynamodb scan --table-name $TABLE_NAME \
       --filter-expression "#c = :seeded" \
       --expression-attribute-names '{"#c":"Comment"}' \
       --expression-attribute-values '{":seeded":{"S":"Seeded Account: Unused accounts bulk seeding"}}' \
       --select COUNT --region $REGION --endpoint-url $ENDPOINT | jq '.Count')
     COUNT=$(aws dynamodb scan --table-name $TABLE_NAME \
       --filter-expression "#c = :seeded AND (attribute_not_exists(LastLogin) OR LastLogin < :threshold)" \
       --expression-attribute-names '{"#c":"Comment"}' \
       --expression-attribute-values "{\":seeded\":{\"S\":\"Seeded Account: Unused accounts bulk seeding\"},\":threshold\":{\"S\":\"$THRESHOLD\"}}" \
       --select COUNT --region $REGION --endpoint-url $ENDPOINT | jq '.Count')
     echo "$COUNT seeded unused accounts remaining (should be 0)"
     echo "$((SEEDED_COUNT - COUNT)) seeded accounts kept because they logged in within 6 months (expected)"
     echo "$((TOTAL_COUNT - SEEDED_COUNT)) other rows kept (standard test users and IDENTITY# pins - expected)"

    # IDENTITY#<identity> pinning rows (see service-api ActorUsers::add) must be deleted with their account,
    # and must never be deleted while an account with that identity still exists
    echo ""
    echo "Identity pins:"
    PINS=$(aws dynamodb scan --table-name $TABLE_NAME \
      --filter-expression "begins_with(Id, :p)" \
      --expression-attribute-values '{":p":{"S":"IDENTITY#"}}' \
      --projection-expression "Id" --region $REGION --endpoint-url $ENDPOINT --output json \
      | jq -r '.Items[].Id.S | sub("^IDENTITY#"; "")' | sort -u)
    IDENTITIES=$(aws dynamodb scan --table-name $TABLE_NAME \
      --filter-expression "attribute_exists(#i)" --expression-attribute-names '{"#i":"Identity"}' \
      --projection-expression "#i" --region $REGION --endpoint-url $ENDPOINT --output json \
      | jq -r '.Items[].Identity.S' | sort -u)
    ORPHAN_PINS=$(comm -23 <(echo "$PINS") <(echo "$IDENTITIES") | grep -c . || true)
    MISSING_PINS=$(comm -13 <(echo "$PINS") <(echo "$IDENTITIES") | grep -c . || true)
    echo "$ORPHAN_PINS orphaned IDENTITY# pins with no account (should be 0)"
    echo "$MISSING_PINS accounts whose IDENTITY# pin is missing (should be 0)"

    echo ""
    echo "Plans in done folder:"
    DONE_COUNT=$(aws s3 ls s3://$BUCKET/done/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive | wc -l)
    echo "$DONE_COUNT plans archived"

    echo ""
    echo "Plans in skipped folder (account no longer eligible at execution time):"
    SKIPPED_COUNT=$(aws s3 ls s3://$BUCKET/skipped/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive | wc -l)
    echo "$SKIPPED_COUNT plans skipped"

    echo ""
    echo "Plans still in plan folder:"
    PLAN_COUNT=$(aws s3 ls s3://$BUCKET/plan/ \
      --region $REGION --endpoint-url $ENDPOINT --recursive | wc -l)
    echo "$PLAN_COUNT plans remaining (should be 0)"

    if [ "$COUNT" -eq 0 ] && [ "$PLAN_COUNT" -eq 0 ] && [ "$ORPHAN_PINS" -eq 0 ] && [ "$MISSING_PINS" -eq 0 ]; then
      echo ""
      echo "✓ Deletion successful!"
    else
      echo ""
      echo "✗ Deletion may have failed"
    fi
    ;;

  cleanup)
    echo "=== Cleaning up ==="
    echo "Stopping services..."
    $MAKE down
    echo "✓ Services stopped"
    ;;

  logs)
    echo "=== Lambda Logs ==="
    $MAKE logs unused-accounts-lambda
    ;;

  *)
    cat <<EOF
Usage: $SELF <command>

Commands:
  seed      - Create bucket and seed test data (10k unused accounts) into LocalStack DynamoDB
  discover  - Export DynamoDB to S3, load it into a local Trino (Athena stand-in), run the
              unused accounts query and write work files to s3://<bucket>/todo/
  plan      - Generate deletion plans from the work files (Execute=false)
  review [N] - Show total plan count and the first N deletion plans (default 3)
  execute   - Execute deletion of all planned accounts (Execute=true)
  verify    - Check deletion completed successfully
  logs      - Stream lambda logs
  cleanup   - Stop all services
  help      - Show this message

IMPORTANT: Before running seed, LocalStack must be running!
  1. make up                                    # Start all services (includes LocalStack)
  2. Wait 45-60 seconds for LocalStack to be healthy
  3. $SELF seed             # The script will verify LocalStack is ready

Quick workflow:
  make down && make up
  sleep 45
  $SELF seed                # Seeds 10k test accounts
  $SELF discover            # Athena-style query (local Trino) -> todo/ work files
  $SELF plan                # Generates deletion plans from the work files
  $SELF review              # Shows sample plans
  $SELF execute             # Executes deletion
  $SELF verify              # Verifies results
  make down                                     # Stops services

Local Athena (Trino):
  - LocalStack Community has no Athena, so 'discover' starts a Trino container (Athena engine v3 is Trino)
    using the "unused-accounts" compose profile. It is not started by 'make up'.
  - The same discover_unused_accounts.py and SQL used against Athena in AWS run against it.
  - Trino UI/API: http://localhost:8085
  - Requires 'uv' to run the Python scripts (brew install uv)

LocalStack Configuration:
  - LocalStack runs on port 4566 (must be exposed for host-level access)
  - DynamoDB tables created without prefix (ActorUsers, UserLpaActorMap, ...) by the api-seeding
    container (service-api/docker/seeding/start.sh), which 'make up' runs
  - S3 bucket: unused-accounts-bucket
  - Endpoint: http://localhost:4566 (from host) or http://localstack:4566 (from container)

If you get "connection refused" error:
  1. Make sure 'make up' has completed (check 'make ps')
  2. Wait at least 45 seconds after 'make up' for LocalStack to be healthy
  3. The script now automatically waits for LocalStack to be ready
  4. Check 'make logs localstack' to verify it started

If you get "security token" or credential errors:
  1. This can happen if real AWS credentials (from aws-vault, ~/.aws/credentials, etc.) are in your environment
  2. The script now automatically unsets credential-related env vars and uses dummy credentials
  3. If errors persist, try running: unset AWS_VAULT AWS_PROFILE AWS_ROLE_ARN before running the test script
  4. Verify LocalStack is healthy: curl http://localhost:4566/_localstack/health

Environment variables (optional):
  REGION=eu-west-1                  # AWS region
  ENDPOINT=http://localhost:4566     # LocalStack endpoint
  BUCKET=unused-accounts-bucket      # S3 bucket name
  LAMBDA=unused-accounts-lambda      # Lambda docker-compose service name
  LAMBDA_INVOKE_URL=http://localhost:9008/2015-03-31/functions/function/invocations  # RIE invoke URL
  TRINO_URL=http://localhost:8085    # Local Trino (Athena stand-in)
  TABLE_PREFIX=""                    # DynamoDB table prefix (empty for no prefix)

EOF
    exit 0
    ;;
esac
exit
}
