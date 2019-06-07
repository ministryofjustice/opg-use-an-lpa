#!/bin/sh

echo Starting seeding

if ! [[ -z "${AWS_ENDPOINT_DYNAMODB}" ]]; then

    # If we're not running against AWS' endpoint

    DYNAMODN_ENDPOINT=http://${AWS_ENDPOINT_DYNAMODB}

    echo Using local DynamoDB

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

# Run the seeding script
# This is written in Python as managing the variable DynamoDB endpoint isn't easy in a shell script.
python /app/seeding/dynamodb.py

echo Finished seeding
