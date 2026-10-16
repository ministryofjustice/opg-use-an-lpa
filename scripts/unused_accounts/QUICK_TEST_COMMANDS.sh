#!/bin/bash
# Quick copy-paste commands for testing the unused accounts lambda locally.
# Prints the commands in order. Run from the repository root.
# Full guide: LOCAL_TESTING_GUIDE.md

T=scripts/unused_accounts/test_unused_accounts.sh

echo "=== STEP 1: Start services ==="
echo "make down && make up"
echo ""

echo "=== STEP 2: Create the bucket and seed 10k unused accounts ==="
echo "$T seed"
echo ""

echo "=== STEP 3: Discover unused accounts (local Trino stands in for Athena) -> todo/ work files ==="
echo "$T discover"
echo ""

echo "=== STEP 4: Generate deletion plans (Execute=false) -> plan/ ==="
echo "$T plan"
echo ""

echo "=== STEP 5: Review plans (total + first N) ==="
echo "$T review 5"
echo ""

echo "=== STEP 6: Execute deletion (Execute=true, asks for confirmation, ~5 min for 10k) ==="
echo "$T execute"
echo ""

echo "=== STEP 7: Verify ==="
echo "$T verify"
echo ""

echo "=== Invoke the lambda directly (Runtime Interface Emulator on port 9008, not LocalStack) ==="
echo "curl -s -XPOST http://localhost:9008/2015-03-31/functions/function/invocations -d '{\"Execute\": false}' | jq ."
echo ""

echo "=== Inspect S3 and DynamoDB (LocalStack, tables have no prefix) ==="
echo "aws s3 ls s3://unused-accounts-bucket/ --recursive --endpoint-url http://localhost:4566 --region eu-west-1 | awk '{print \$4}' | cut -d/ -f1 | sort | uniq -c"
echo "aws dynamodb scan --table-name ActorUsers --select COUNT --endpoint-url http://localhost:4566 --region eu-west-1 | jq .Count"
echo ""

echo "=== Lambda logs ==="
echo "make logs unused-accounts-lambda"
echo ""

echo "=== Cleanup (also stops Trino) ==="
echo "make down"

