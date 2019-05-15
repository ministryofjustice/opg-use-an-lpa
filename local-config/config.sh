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
fi
