#!/bin/sh

#
#  Setup Secrets Manager
#
/usr/local/bin/waitforit -address=http://localstack:4584 -timeout 30 -retry 2000 -debug

if [ $? -ne 0 ]; then
    echo "Secrets Manager failed to start"
else
    aws secretsmanager create-secret \
    --name "$SECRET_SESSION_KEYS_KEY" \
    --secret-string "$SECRET_SESSION_KEYS_VALUE" \
    --endpoint-url=http://localstack:4584 \
    --region eu-west-1
fi
