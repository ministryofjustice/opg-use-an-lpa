#!/bin/sh

script_path=$(dirname $0)
rm -f ${script_path}/mock-openapi.yaml
rm -f ${script_path}/opg-data-lpa-openapi.yml

wget -O ${script_path}/opg-data-lpa-openapi.yml https://raw.githubusercontent.com/ministryofjustice/opg-data-lpa/main/lambda_functions/v1/openapi/lpa-openapi.yml
sed -i -e 's|${allowed_roles}||' ${script_path}/opg-data-lpa-openapi.yml # removes x-amazon-apigateway-policy principles variable
yq ea '. as $item ireduce ({}; . * $item )' ${script_path}/mock-openapi-examples.yaml ${script_path}/opg-data-lpa-openapi.yml > ${script_path}/mock-openapi.yaml

rm -f ${script_path}/opg-data-lpa-openapi.yml-e
