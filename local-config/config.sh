#!/bin/sh

#
#  Setup DynamoDB
#
/usr/local/bin/waitforit -address=tcp://localstack:4569 -timeout 60 -retry 6000 -debug

if [ $? -ne 0 ]; then
    echo "DynamoDB failed to start"
else
    echo "DynamoDB ready"

    # Setup tables

    aws dynamodb create-table \
    --attribute-definitions AttributeName=ViewerCode,AttributeType=S \
    --table-name ViewerCodes \
    --key-schema AttributeName=ViewerCode,KeyType=HASH \
    --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10 \
    --region eu-west-1 \
    --endpoint http://localstack:4569

    # Inject dummy data
    aws dynamodb put-item \
    --table-name ViewerCodes \
    --item '{"ViewerCode": {"S": "123456789012"}, "SiriusId": {"S": "12345678901"}, "Expires": {"S": "2020-01-01 12:34:56"}}' \
    --region eu-west-1 \
    --endpoint http://localstack:4569

    aws dynamodb put-item \
    --table-name ViewerCodes \
    --item '{"ViewerCode": {"S": "987654321098"}, "SiriusId": {"S": "98765432109"}, "Expires": {"S": "2020-01-01 12:34:56"}}' \
    --region eu-west-1 \
    --endpoint http://localstack:4569

    aws dynamodb put-item \
    --table-name ViewerCodes \
    --item '{"ViewerCode": {"S": "222222222222"}, "SiriusId": {"S": "22222222222"}, "Expires": {"S": "2019-01-01 12:34:56"}}' \
    --region eu-west-1 \
    --endpoint http://localstack:4569

fi
