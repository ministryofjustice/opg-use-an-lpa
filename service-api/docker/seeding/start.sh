#!/bin/sh

DYNAMODN_ENDPOINT=""

if ! [[ -z "${AWS_ENDPOINT_DYNAMODB}" ]]; then

    # If we're not running against AWS' endpoint

    DYNAMODN_ENDPOINT=http://${AWS_ENDPOINT_DYNAMODB}

    export WAITFORIT_VERSION="v2.4.1"
    wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64
    chmod +x /usr/local/bin/waitforit

    /usr/local/bin/waitforit -address=tcp://${AWS_ENDPOINT_DYNAMODB} -timeout 60 -retry 6000 -debug

    # ----------------------------------------------------------
    # Add any setup here that is performed with Terraform in AWS.

    aws dynamodb create-table \
    --attribute-definitions AttributeName=ViewerCode,AttributeType=S \
    --table-name ViewerCodes \
    --key-schema AttributeName=ViewerCode,KeyType=HASH \
    --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10 \
    --region eu-west-1 \
    --endpoint $DYNAMODN_ENDPOINT
fi

# Inject dummy data
aws dynamodb put-item \
--table-name ViewerCodes \
--item '{"ViewerCode": {"S": "123456789012"}, "SiriusId": {"S": "12345678901"}, "Expires": {"S": "2020-01-01 12:34:56"}}' \
--region eu-west-1 \
--endpoint $DYNAMODN_ENDPOINT

aws dynamodb put-item \
--table-name ViewerCodes \
--item '{"ViewerCode": {"S": "987654321098"}, "SiriusId": {"S": "98765432109"}, "Expires": {"S": "2020-01-01 12:34:56"}}' \
--region eu-west-1 \
--endpoint $DYNAMODN_ENDPOINT

aws dynamodb put-item \
--table-name ViewerCodes \
--item '{"ViewerCode": {"S": "222222222222"}, "SiriusId": {"S": "22222222222"}, "Expires": {"S": "2019-01-01 12:34:56"}}' \
--region eu-west-1 \
--endpoint $DYNAMODN_ENDPOINT

# Output the table's content for debugging.
aws dynamodb scan \
--table-name ViewerCodes \
--region eu-west-1 \
--endpoint $DYNAMODN_ENDPOINT
