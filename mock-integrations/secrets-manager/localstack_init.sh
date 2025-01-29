#!/bin/sh
# Set secrets in Secrets Manager
awslocal secretsmanager create-secret --name gov-uk-onelogin-identity-public-key \
    --region "eu-west-1" \
    --description "Local development public key" \
    --secret-string file:///public_key.pem

awslocal secretsmanager create-secret --name gov-uk-onelogin-identity-private-key \
    --region "eu-west-1" \
    --description "Local development private key" \
    --secret-string file:///private_key.pem

awslocal secretsmanager create-secret --name lpa-data-store-secret \
    --region "eu-west-1" \
    --description "Local development lpa store secret" \
    --secret-string "A shared secret string that needs to be at least 128 bits long"

# TODO hopefully can ditch these lines if use awslocal commands instead of aws
aws configure set aws_access_key_id "dummy" --profile use-profile
aws configure set aws_secret_access_key "dummy" --profile use-profile
aws configure set region "eu-west-1" --profile use-profile
aws configure set output "table" --profile use-profile

aws --endpoint-url=http://localhost:4566 sns create-topic --name order-creation-events --region eu-west-1 --profile use-profile --output table | cat

echo "Creating SQS queue"
#aws --endpoint-url=http://localhost:4566 sqs create-queue --queue-name local-notifications --region eu-west-1 --profile use-profile --output table
awslocal sqs create-queue --queue-name local-notifications --attributes '{"MaximumMessageSize": "102400"}'

awslocal sqs get-queue-attributes --queue-url http://sqs.eu-west-1.localhost.localstack.cloud:4566/000000000000/local-notifications --attribute-names All

awslocal scheduler create-schedule \
    --name sqs-templated-schedule \
    --schedule-expression 'rate(5 minutes)' \
    --target '{"RoleArn": "arn:aws:iam::000000000000:role/schedule-role", "Arn":"arn:aws:sqs:eu-west-1:000000000000:local-notifications", "Input": "test" }' \
    --flexible-time-window '{ "Mode": "OFF"}'

awslocal scheduler tag-resource \
    --resource-arn arn:aws:scheduler:eu-west-1:000000000000:schedule/default/sqs-templated-schedule \
    --tags Key=Name,Value=Test

awslocal scheduler list-tags-for-resource --resource-arn arn:aws:scheduler:eu-west-1:000000000000:schedule/default/sqs-templated-schedule

echo "Creating lambda"

awslocal lambda create-function \
    --function-name event-receiver-lambda \
    --runtime go1.x \
    --zip-file fileb://lambda-functions/event-receiver.zip \
    --handler main \
    --role arn:aws:iam::000000000000:role/lambda-role

echo "Creating event source mapping"

awslocal lambda create-event-source-mapping \
         --function-name function \
         --batch-size 1 \
         --event-source-arn arn:aws:sqs:eu-west-1:000000000000:local-notifications
