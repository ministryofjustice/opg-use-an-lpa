#!/bin/sh

script_path=$(dirname $0)
rm -f ${script_path}/mock-openapi.yaml

wget -O ${script_path}/mock-openapi.yml https://raw.githubusercontent.com/ministryofjustice/opg-data-lpa-codes/refs/heads/main/lambda_functions/v1/openapi/lpa-codes-openapi-aws.compiled.yml
