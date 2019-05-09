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
fi
