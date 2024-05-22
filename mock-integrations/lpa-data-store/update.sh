#!/bin/sh

script_path=$(dirname $0)
rm -f ${script_path}/mock-openapi.yaml
rm -f ${script_path}/opg-data-lpa-data-store-openapi.yml

wget -O ${script_path}/opg-data-lpa-data-store-openapi.yml https://raw.githubusercontent.com/ministryofjustice/opg-data-lpa-store/main/docs/openapi/openapi.yaml
yq ". *= load(\"${script_path}/mock-openapi-examples.yaml\")" ${script_path}/opg-data-lpa-data-store-openapi.yml > ${script_path}/mock-openapi.yaml

rm -f ${script_path}/opg-data-lpa-data-store-openapi.yml-e
