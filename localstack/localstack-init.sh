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

echo "Configuring events"
awslocal sqs create-queue --region "eu-west-1" --queue-name event-bus-queue
awslocal events create-event-bus --region "eu-west-1" --name default

awslocal events put-rule \
  --region "eu-west-1" \
  --name send-events-to-bus-queue-rule \
  --event-bus-name default \
  --event-pattern '{"source":["opg.poas.makeregister"],"detail-type":["lpa-access-granted"]}'

awslocal events put-targets \
  --region "eu-west-1" \
  --event-bus-name default \
  --rule send-events-to-bus-queue-rule \
  --targets "Id"="event-bus-queue","Arn"="arn:aws:sqs:eu-west-1:000000000000:event-bus-queue"

echo "Creating lambda"
awslocal lambda create-function \
    --function-name event-receiver-lambda \
    --runtime provided.al2023 \
    --zip-file fileb:///event-receiver.zip \
    --handler main \
    --role arn:aws:iam::000000000000:role/lambda-role \
    --region "eu-west-1"

awslocal lambda wait function-active-v2 --region eu-west-1 --function-name event-receiver-lambda

echo "Creating event source mapping"
awslocal lambda create-event-source-mapping \
    --function-name event-receiver-lambda \
    --batch-size 1 \
    --event-source-arn arn:aws:sqs:eu-west-1:000000000000:event-bus-queue \
    --region "eu-west-1"
