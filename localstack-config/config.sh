#!/bin/sh

#
#  Setup Secrets Manager
#
/usr/local/bin/waitforit -address=http://localstack:4584 -timeout 30 -retry 2000 -debug

if [ $? -ne 0 ]; then
    echo "Secrets Manager failed to start"
else
    aws secretsmanager create-secret \
        --name 'session-keys' \
        --secret-string '{"1":"763C683D6F62536EC4A8694FEFBD1", "2":"DB63C574D8CEBBAD2EEACEA9E9D68", "3":null}' \
        --endpoint-url=http://localstack:4584 \
        --region eu-west-2
fi
