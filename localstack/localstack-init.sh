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

echo "Creating SQS queue"
awslocal sqs create-queue --region "eu-west-1" --queue-name receive-events-queue --attributes '{"MaximumMessageSize": "102400"}' 

awslocal sqs get-queue-attributes --region "eu-west-1" --queue-url http://sqs.eu-west-1.localhost.localstack.cloud:4566/000000000000/receive-events-queue --attribute-names All

echo "Creating EventBridge Rule"
awslocal events put-rule \
    --name mlpa-events-to-use \
    --schedule-expression 'rate(2 minutes)'
    --region "eu-west-1"

awslocal lambda add-permission \
    --function-name event-receiver-lambda \
    --statement-id my-scheduled-event \
    --action 'lambda:InvokeFunction' \
    --principal events.amazonaws.com \
    --source-arn arn:aws:events:eu-west-1:000000000000:rule/mlpa-events-to-use


echo "Creating schedule"
awslocal scheduler create-schedule \
    --name sqs-templated-schedule \
    --schedule-expression 'rate(5 minutes)' \
    --target '{"RoleArn": "arn:aws:iam::000000000000:role/schedule-role", "Arn":"arn:aws:sqs:eu-west-1:000000000000:receive-events-queue", "Input": "test" }' \
    --flexible-time-window '{ "Mode": "OFF"}' \
    --region "eu-west-1" 

awslocal scheduler tag-resource \
    --resource-arn arn:aws:scheduler:eu-west-1:000000000000:schedule/default/sqs-templated-schedule \
    --tags Key=Name,Value=Test

awslocal scheduler list-tags-for-resource --resource-arn arn:aws:scheduler:eu-west-1:000000000000:schedule/default/sqs-templated-schedule

echo "Creating lambda"

awslocal lambda create-function \
    --function-name event-receiver-lambda \
    --runtime go1.x \
    --zip-file fileb:///event-receiver.zip \
    --handler main \
    --role arn:aws:iam::000000000000:role/lambda-role \
    --region "eu-west-1" 

echo "Creating event source mapping"

awslocal lambda create-event-source-mapping \
         --function-name event-receiver-lambda \
         --batch-size 1 \
         --event-source-arn arn:aws:sqs:eu-west-1:000000000000:receive-events-queue \
         --region "eu-west-1" 

echo "Add Lambda function as target"
awslocal events put-targets \
    --rule mlpa-events-to-use \
    --targets file:///targets.json