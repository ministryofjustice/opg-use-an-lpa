#!/usr/bin/env bash

# Assume role in management account
temp_role=$(aws sts assume-role --role-arn "arn:aws:iam::311462405659:role/ci"\
                               --role-session-name "ci")
export AWS_ACCESS_KEY_ID=$(echo $temp_role | jq .Credentials.AccessKeyId | xargs)
export AWS_SECRET_ACCESS_KEY=$(echo $temp_role | jq .Credentials.SecretAccessKey | xargs)
export AWS_SESSION_TOKEN=$(echo $temp_role | jq .Credentials.SessionToken | xargs)

eval $(aws ecr get-login --no-include-email --region=eu-west-1)
