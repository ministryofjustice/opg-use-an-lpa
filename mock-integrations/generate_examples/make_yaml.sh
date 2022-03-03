#!/usr/bin/env bash

rm -f swagger.yaml
rm -f temp.yaml
touch swagger.yaml
touch temp.yaml
cat examples-template.yaml >> temp.yaml
for n in $(cat list.txt )
do
    echo "Working on $n..."
    example_name="lpa${n: -5:4}"
    lpa_yaml_data=$(python ../../scripts/call-api-gateway/call_api_gateway.py $n | yq -P)
    lpa_example_name=$example_name lpa_data=$lpa_yaml_data \
    yq 'with(.paths[].get.responses.[].content.[].examples; . | .[env(lpa_example_name)].value=env(lpa_data))' \
    temp.yaml >> temp.yaml
done

yq ea '. as $item ireduce ({}; . * $item )' temp.yaml > mock-openapi-examples.yaml

rm -f temp.yaml
