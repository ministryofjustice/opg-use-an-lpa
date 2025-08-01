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
    --attribute-definitions AttributeName=Id,AttributeType=S AttributeName=Identity,AttributeType=S AttributeName=Email,AttributeType=S AttributeName=NewEmail,AttributeType=S AttributeName=ActivationToken,AttributeType=S AttributeName=PasswordResetToken,AttributeType=S AttributeName=EmailResetToken,AttributeType=S \
    --table-name ActorUsers \
    --key-schema AttributeName=Id,KeyType=HASH \
    --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10 \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --global-secondary-indexes \
      IndexName=IdentityIndex,KeySchema=["{AttributeName=Identity,KeyType=HASH}"],Projection="{ProjectionType=ALL}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}" \
      IndexName=EmailIndex,KeySchema=["{AttributeName=Email,KeyType=HASH}"],Projection="{ProjectionType=ALL}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}" \
      IndexName=NewEmailIndex,KeySchema=["{AttributeName=NewEmail,KeyType=HASH}"],Projection="{ProjectionType=ALL}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}" \
      IndexName=ActivationTokenIndex,KeySchema=["{AttributeName=ActivationToken,KeyType=HASH}"],Projection="{ProjectionType=KEYS_ONLY}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}" \
      IndexName=PasswordResetTokenIndex,KeySchema=["{AttributeName=PasswordResetToken,KeyType=HASH}"],Projection="{ProjectionType=KEYS_ONLY}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}"\
      IndexName=EmailResetTokenIndex,KeySchema=["{AttributeName=EmailResetToken,KeyType=HASH}"],Projection="{ProjectionType=KEYS_ONLY}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}"

    aws dynamodb update-time-to-live \
    --table-name ActorUsers \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --time-to-live-specification "Enabled=true, AttributeName=ExpiresTTL"

    aws dynamodb create-table \
    --attribute-definitions AttributeName=ViewerCode,AttributeType=S AttributeName=SiriusUid,AttributeType=S AttributeName=Expires,AttributeType=S \
    --table-name ViewerCodes \
    --key-schema AttributeName=ViewerCode,KeyType=HASH \
    --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10 \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --global-secondary-indexes IndexName=SiriusUidIndex,KeySchema=["{AttributeName=SiriusUid,KeyType=HASH},{AttributeName=Expires,KeyType=RANGE}"],Projection="{ProjectionType=ALL}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}"

    aws dynamodb create-table \
    --attribute-definitions AttributeName=ViewerCode,AttributeType=S AttributeName=Viewed,AttributeType=S \
    --table-name ViewerActivity \
    --key-schema AttributeName=ViewerCode,KeyType=HASH AttributeName=Viewed,KeyType=RANGE \
    --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10 \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT

   aws dynamodb create-table \
      --attribute-definitions AttributeName=TimePeriod,AttributeType=S \
      --table-name Stats \
      --key-schema AttributeName=TimePeriod,KeyType=HASH \
      --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10 \
      --region eu-west-1 \
      --endpoint $DYNAMODB_ENDPOINT

    aws dynamodb create-table \
    --attribute-definitions AttributeName=Id,AttributeType=S AttributeName=UserId,AttributeType=S AttributeName=ActivationCode,AttributeType=S \
     AttributeName=SiriusUid,AttributeType=S \
    --table-name UserLpaActorMap \
    --key-schema AttributeName=Id,KeyType=HASH \
    --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10 \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --global-secondary-indexes IndexName=UserIndex,KeySchema=["{AttributeName=UserId,KeyType=HASH}"],Projection="{ProjectionType=ALL}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}"\
      IndexName=ActivationCodeIndex,KeySchema=["{AttributeName=ActivationCode,KeyType=HASH}"],Projection="{ProjectionType=ALL}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}"\
      IndexName=SiriusUidIndex,KeySchema=["{AttributeName=SiriusUid,KeyType=HASH}"],Projection="{ProjectionType=ALL}",ProvisionedThroughput="{ReadCapacityUnits=10,WriteCapacityUnits=10}"

     aws dynamodb update-time-to-live \
    --table-name UserLpaActorMap \
    --region eu-west-1 \
    --endpoint $DYNAMODB_ENDPOINT \
    --time-to-live-specification "Enabled=true, AttributeName=ActivateBy"

fi

# Seed UaLPA database
python /app/seeding/dynamodb.py

echo Finished seeding
