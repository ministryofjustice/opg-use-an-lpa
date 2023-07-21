#!/usr/bin/env bash
set -e

rm -f temp.yaml
touch temp.yaml
cat examples-template.yaml >> temp.yaml
cp nginx-template.conf nginx.conf
for n in $(cat list.txt )
do
    echo "Working on $n..."
    example_name="lpa${n: -4:4}"
    lpa_yaml_data=$(python3 ../../../scripts/call-api-gateway/call_api_gateway.py $n | yq -P)
    lpa_example_name=$example_name lpa_data=$lpa_yaml_data \
    yq 'with(.paths[].get.responses.[].content.[].examples; . | .[env(lpa_example_name)].value=env(lpa_data))' \
    temp.yaml >> temp.yaml
    sed -i '' -e "5s/^//p; 6s/^.*/    \"~$n\" \"$example_name\";/" ../../nginx.conf
done

yq ea '. as $item ireduce ({}; . * $item )' temp.yaml > ../mock-openapi-examples.yaml

rm -f temp.yaml
