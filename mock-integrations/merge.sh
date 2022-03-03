#!/bin/sh

script_path=$(dirname $0)
rm -f ${script_path}/openapi.yaml
rm -f ${script_path}/lpa-openapi.yml

wget -O ${script_path}/lpa-openapi.yml https://raw.githubusercontent.com/ministryofjustice/opg-data-lpa/main/lambda_functions/v1/openapi/lpa-openapi.yml
sed -i -e 's|${allowed_roles}||' ${script_path}/lpa-openapi.yml # removes x-amazon-apigateway-policy principles variable
yq ea '. as $item ireduce ({}; . * $item )' ${script_path}/openapi-examples.yaml ${script_path}/lpa-openapi.yml > ${script_path}/openapi.yaml

rm -f ${script_path}/lpa-openapi.yml-e
