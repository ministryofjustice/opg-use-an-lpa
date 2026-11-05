#!/bin/bash
set -euo pipefail

export AWS_DEFAULT_REGION=eu-west-1
export AWS_PAGER=""
export AWS_ENDPOINT_URL="${AWS_ENDPOINT_URL:-http://localstack:4566}"

aws() {
    command aws --endpoint-url "$AWS_ENDPOINT_URL" --region "$AWS_DEFAULT_REGION" "$@"
}

# Ignore only the expected "already exists" response; other AWS errors must stop setup.
create_resource() {
    local expected_error="$1"
    shift
    local output
    if output=$(aws "$@" 2>&1); then
        printf '%s\n' "$output"
    elif [[ "$output" == *"($expected_error)"* ]]; then
        printf '%s\n' "$output"
    else
        printf '%s\n' "$output" >&2
        return 1
    fi
}

setup_resources() {
    # Secrets Manager
    create_resource ResourceExistsException secretsmanager create-secret \
        --name gov-uk-onelogin-identity-public-key --secret-string file:///opt/secrets/public_key.pem
    aws secretsmanager put-secret-value \
        --secret-id gov-uk-onelogin-identity-public-key --secret-string file:///opt/secrets/public_key.pem

    create_resource ResourceExistsException secretsmanager create-secret \
        --name gov-uk-onelogin-identity-private-key --secret-string file:///opt/secrets/private_key.pem
    aws secretsmanager put-secret-value \
        --secret-id gov-uk-onelogin-identity-private-key --secret-string file:///opt/secrets/private_key.pem

    create_resource ResourceExistsException secretsmanager create-secret \
        --name lpa-data-store-secret \
        --secret-string "A shared secret string that needs to be at least 128 bits long"
    aws secretsmanager put-secret-value \
        --secret-id lpa-data-store-secret \
        --secret-string "A shared secret string that needs to be at least 128 bits long"

    # KMS: use the returned key ID rather than LocalStack's custom key tags.
    local key_id
    key_id=$(aws kms list-aliases \
        --query "Aliases[?AliasName=='alias/viewer-sessions-cmk-alias'].TargetKeyId | [0]" --output text)
    if [[ "$key_id" == None ]]; then
        key_id=$(aws kms create-key --query KeyMetadata.KeyId --output text)
        aws kms create-alias --alias-name alias/viewer-sessions-cmk-alias --target-key-id "$key_id"
    fi

    # Event queue and local work buckets
    aws sqs create-queue --queue-name event-bus-queue --attributes VisibilityTimeout=5400
    create_resource BucketAlreadyOwnedByYou s3api create-bucket \
        --bucket duplicate-accounts-bucket --create-bucket-configuration LocationConstraint=eu-west-1
    create_resource BucketAlreadyOwnedByYou s3api create-bucket \
        --bucket unused-accounts-bucket --create-bucket-configuration LocationConstraint=eu-west-1

    create_resource EntityAlreadyExists iam create-role \
        --role-name lambda-role \
        --assume-role-policy-document '{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Principal":{"Service":"lambda.amazonaws.com"},"Action":"sts:AssumeRole"}]}'
}

deploy_function() {
    local name="$1" memory="$2" environment="$3"
    local function_name="${name}-lambda"
    local image_uri="${LAMBDA_IMAGE_PREFIX}-${name}:local"
    local existing
    existing=$(aws lambda list-functions \
        --query "Functions[?FunctionName=='${function_name}'].FunctionName | [0]" --output text)

    if [[ "$existing" == None ]]; then
        aws lambda create-function \
            --function-name "$function_name" \
            --package-type Image --code "ImageUri=${image_uri}" \
            --architectures "$LAMBDA_ARCHITECTURE" \
            --role "$role_arn" --timeout 900 --memory-size "$memory" \
            --environment "$environment"
    else
        aws lambda update-function-code \
            --function-name "$function_name" --image-uri "$image_uri" \
            --architectures "$LAMBDA_ARCHITECTURE"
        aws lambda wait function-updated-v2 --function-name "$function_name"
        aws lambda update-function-configuration \
            --function-name "$function_name" \
            --role "$role_arn" --timeout 900 --memory-size "$memory" \
            --environment "$environment"
        aws lambda wait function-updated-v2 --function-name "$function_name"
    fi
    aws lambda wait function-active-v2 --function-name "$function_name"
}

setup_lambdas() {
    : "${LAMBDA_IMAGE_PREFIX:?Set LAMBDA_IMAGE_PREFIX}"
    case "${LAMBDA_ARCHITECTURE:-}" in
        arm64|x86_64) ;;
        *) echo "Unsupported Lambda architecture: ${LAMBDA_ARCHITECTURE:-unset}" >&2; return 1 ;;
    esac

    local account role_arn queue_arn failed_targets mapping_count
    account=$(aws sts get-caller-identity --query Account --output text)
    role_arn="arn:aws:iam::${account}:role/lambda-role"
    queue_arn="arn:aws:sqs:eu-west-1:${account}:event-bus-queue"

    deploy_function event-receiver 128 \
        "Variables={AWS_ENDPOINT_DYNAMODB=${AWS_ENDPOINT_URL}}"
    deploy_function upload-statistics 1024 \
        "Variables={ENVIRONMENT=local,REGION=eu-west-1,AWS_ENDPOINT_DYNAMODB=${AWS_ENDPOINT_URL}}"
    deploy_function unused-accounts 1024 \
        "Variables={BUCKET=unused-accounts-bucket,WORK_FILE_PREFIX=todo,PLAN_FILE_PREFIX=plan,MONTHS_INACTIVE=6,AWS_ENDPOINT_DYNAMODB=${AWS_ENDPOINT_URL},AWS_ENDPOINT_S3=${AWS_ENDPOINT_URL}}"

    # Enable delivery only after the database and all functions are ready.
    aws events put-rule \
        --name send-events-to-bus-queue-rule --event-bus-name default \
        --event-pattern '{"source":["opg.poas.makeregister"],"detail-type":["lpa-access-granted"]}'
    failed_targets=$(aws events put-targets \
        --event-bus-name default --rule send-events-to-bus-queue-rule \
        --targets "Id=event-bus-queue,Arn=${queue_arn}" \
        --query FailedEntryCount --output text)
    if [[ "$failed_targets" != 0 ]]; then
        echo "Unable to register event target: ${failed_targets} failed entries" >&2
        return 1
    fi

    mapping_count=$(aws lambda list-event-source-mappings \
        --function-name event-receiver-lambda --event-source-arn "$queue_arn" \
        --query 'length(EventSourceMappings)' --output text)
    if [[ "$mapping_count" == 0 ]]; then
        aws lambda create-event-source-mapping \
            --function-name event-receiver-lambda --batch-size 1 \
            --event-source-arn "$queue_arn" --enabled
    fi
}

case "${1:-}" in
    resources) setup_resources ;;
    lambdas) setup_lambdas ;;
    *) echo "Usage: $0 {resources|lambdas}" >&2; exit 1 ;;
esac
