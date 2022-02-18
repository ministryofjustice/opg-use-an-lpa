#!/bin/sh

script_path=$(dirname $0)
rm -f ${script_path}/swagger.yaml

sed -i -e 's|${allowed_roles}||' ${script_path}/lpa-openapi.yml
yq ea '. as $item ireduce ({}; . * $item )' ${script_path}/swagger-examples.yaml ${script_path}/lpa-openapi.yml > ${script_path}/swagger.yaml

rm ${script_path}/lpa-openapi.yml-e
