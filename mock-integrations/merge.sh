#!/bin/sh

script_path=$(dirname $0)
rm ${script_path}/swagger.yaml

sed -i -e 's|${allowed_roles}||' ${script_path}/lpa-openapi.yml && \
  python3 ${script_path}/scripts/merge_yaml.py ${script_path}/lpa-openapi.yml ${script_path}/swagger-examples.yaml > ${script_path}/swagger.yaml && \
  chmod +x ${script_path}/run.sh

rm ${script_path}/lpa-openapi.yml-e
