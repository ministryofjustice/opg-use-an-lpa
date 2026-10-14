#!/bin/sh

echo Starting seeding...

# -----
# DynamoDB setup and seeding for UaLPA
# -----

if [[ -n "${AWS_ENDPOINT_DYNAMODB}" ]]; then
    # If we're not running against AWS' endpoint

    DYNAMODB_ENDPOINT=${AWS_ENDPOINT_DYNAMODB}

    echo Using local DynamoDB
    /usr/local/bin/waitforit -address=${AWS_ENDPOINT_DYNAMODB} -timeout 60 -retry 6000 -debug

    # ----------------------------------------------------------
    # Add any setup here that is performed with Terraform in AWS.

    aws dynamodb create-table \
    --attribute-definitions AttributeName=Id,AttributeType=S AttributeName=Identity,AttributeType=S AttributeName=Email,AttributeType=S \
    --table-name demo-ActorUsers \
    --key-schema AttributeName=Id,KeyType=HASH \
    --billing-mode PAY_PER_REQUEST \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --global-secondary-indexes \
      IndexName=IdentityIndex,KeySchema=["{AttributeName=Identity,KeyType=HASH}"],Projection="{ProjectionType=ALL}" \
      IndexName=EmailIndex,KeySchema=["{AttributeName=Email,KeyType=HASH}"],Projection="{ProjectionType=ALL}"

    aws dynamodb update-time-to-live \
    --table-name demo-ActorUsers \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --time-to-live-specification "Enabled=true, AttributeName=ExpiresTTL"

    aws dynamodb create-table \
    --attribute-definitions AttributeName=ViewerCode,AttributeType=S AttributeName=SiriusUid,AttributeType=S AttributeName=Expires,AttributeType=S \
    --table-name demo-ViewerCodes \
    --key-schema AttributeName=ViewerCode,KeyType=HASH \
    --billing-mode PAY_PER_REQUEST \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --global-secondary-indexes IndexName=SiriusUidIndex,KeySchema=["{AttributeName=SiriusUid,KeyType=HASH},{AttributeName=Expires,KeyType=RANGE}"],Projection="{ProjectionType=ALL}"

    aws dynamodb create-table \
    --attribute-definitions AttributeName=ViewerCode,AttributeType=S AttributeName=Viewed,AttributeType=S \
    --table-name demo-ViewerActivity \
    --key-schema AttributeName=ViewerCode,KeyType=HASH AttributeName=Viewed,KeyType=RANGE \
    --billing-mode PAY_PER_REQUEST \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT

    aws dynamodb create-table \
       --attribute-definitions AttributeName=TimePeriod,AttributeType=S \
       --table-name demo-Stats \
       --key-schema AttributeName=TimePeriod,KeyType=HASH \
       --billing-mode PAY_PER_REQUEST \
       --region eu-west-1 \
       --endpoint $DYNAMODB_ENDPOINT

    aws dynamodb create-table \
    --attribute-definitions AttributeName=Id,AttributeType=S AttributeName=UserId,AttributeType=S AttributeName=ActivationCode,AttributeType=S \
     AttributeName=SiriusUid,AttributeType=S \
    --table-name demo-UserLpaActorMap \
    --key-schema AttributeName=Id,KeyType=HASH \
    --billing-mode PAY_PER_REQUEST \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --global-secondary-indexes IndexName=UserIndex,KeySchema=["{AttributeName=UserId,KeyType=HASH}"],Projection="{ProjectionType=ALL}"\
      IndexName=ActivationCodeIndex,KeySchema=["{AttributeName=ActivationCode,KeyType=HASH}"],Projection="{ProjectionType=ALL}"\
      IndexName=SiriusUidIndex,KeySchema=["{AttributeName=SiriusUid,KeyType=HASH}"],Projection="{ProjectionType=ALL}"

     aws dynamodb update-time-to-live \
    --table-name demo-UserLpaActorMap \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --time-to-live-specification "Enabled=true, AttributeName=ActivateBy"

fi

# Seed UaLPA database
python /app/seeding/dynamodb.py

echo Finished seeding
