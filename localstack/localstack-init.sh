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

# KMS setup
awslocal kms create-key \
    --region "eu-west-1" \
    --tags '[{"TagKey":"_custom_id_","TagValue":"bc436485-5092-42b8-92a3-0aa8b93536dc"},{"TagKey":"_custom_key_material_","TagValue":"sM2c0Q2o7JacIpDoCd72i3kA2NdTsFvHUr4Y4YLjJuM="}]'
awslocal kms create-alias \
    --region "eu-west-1" \
    --alias-name "alias/viewer-sessions-cmk-alias" \
    --target-key-id "bc436485-5092-42b8-92a3-0aa8b93536dc"

# Event bus
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

awslocal lambda create-function \
    --function-name event-receiver-lambda \
    --runtime provided.al2023 \
    --zip-file fileb:///event-receiver.zip \
    --handler main \
    --role arn:aws:iam::000000000000:role/lambda-role \
    --region "eu-west-1"

awslocal lambda wait function-active-v2 --region eu-west-1 --function-name event-receiver-lambda

awslocal lambda create-event-source-mapping \
    --function-name event-receiver-lambda \
    --batch-size 1 \
    --event-source-arn arn:aws:sqs:eu-west-1:000000000000:event-bus-queue \
    --region "eu-west-1"

# DynamoDB tables
awslocal dynamodb create-table \
    --table-name ActorUsers \
    --attribute-definitions AttributeName=Id,AttributeType=S AttributeName=Identity,AttributeType=S AttributeName=Email,AttributeType=S \
    --key-schema AttributeName=Id,KeyType=HASH \
    --global-secondary-indexes \
        IndexName=IdentityIndex,Keys=[{AttributeName=Identity,KeyType=HASH}],Projection={ProjectionType=ALL} \
        IndexName=EmailIndex,Keys=[{AttributeName=Email,KeyType=HASH}],Projection={ProjectionType=ALL} \
    --billing-mode PAY_PER_REQUEST \
    --region "eu-west-1"

awslocal dynamodb create-table \
    --table-name UserLpaActorMap \
    --attribute-definitions AttributeName=Id,AttributeType=S AttributeName=UserId,AttributeType=S AttributeName=ActivationCode,AttributeType=S AttributeName=SiriusUid,AttributeType=S \
    --key-schema AttributeName=Id,KeyType=HASH \
    --global-secondary-indexes \
        IndexName=ActivationCodeIndex,Keys=[{AttributeName=ActivationCode,KeyType=HASH}],Projection={ProjectionType=ALL} \
        IndexName=UserIndex,Keys=[{AttributeName=UserId,KeyType=HASH}],Projection={ProjectionType=ALL} \
        IndexName=SiriusUidIndex,Keys=[{AttributeName=SiriusUid,KeyType=HASH}],Projection={ProjectionType=ALL} \
    --billing-mode PAY_PER_REQUEST \
    --region "eu-west-1"

awslocal dynamodb create-table \
    --table-name ViewerCodes \
    --attribute-definitions AttributeName=ViewerCode,AttributeType=S AttributeName=SiriusUid,AttributeType=S AttributeName=Expires,AttributeType=S \
    --key-schema AttributeName=ViewerCode,KeyType=HASH \
    --global-secondary-indexes \
        IndexName=SiriusUidIndex,Keys=[{AttributeName=SiriusUid,KeyType=HASH},{AttributeName=Expires,KeyType=RANGE}],Projection={ProjectionType=ALL} \
    --billing-mode PAY_PER_REQUEST \
    --region "eu-west-1"

# S3 buckets
awslocal s3 mb s3://duplicate-accounts-bucket
