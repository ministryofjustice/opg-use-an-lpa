#!/bin/sh

script_path=$(dirname $0)
rm -f ${script_path}/mock-openapi.yaml
rm -f ${script_path}/opg-data-lpa-instructions-preferences-openapi.yml

wget -O ${script_path}/opg-data-lpa-instructions-preferences-openapi.yml https://raw.githubusercontent.com/ministryofjustice/opg-data-lpa-instructions-preferences/main/docs/openapi/image-request-handler.yml
sed -i -e 's|${allowed_roles}||' ${script_path}/opg-data-lpa-instructions-preferences-openapi.yml # removes x-amazon-apigateway-policy principles variable
yq ea '. as $item ireduce ({}; . * $item )' ${script_path}/mock-openapi-examples.yaml ${script_path}/opg-data-lpa-instructions-preferences-openapi.yml > ${script_path}/mock-openapi.yaml

rm -f ${script_path}/opg-data-lpa-instructions-preferences-openapi.yml-e
