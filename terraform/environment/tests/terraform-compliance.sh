#!/usr/bin/env bash

function terraform-compliance { docker run --rm -v $(pwd):/target -i -t eerkunt/terraform-compliance "$@"; }

rm tests/state.out*

terraform init

terraform refresh
terraform state pull > tests/state.out
terraform show -json tests/state.out > tests/state.out.json
terraform-compliance -p tests/state.out.json -f tests/

rm tests/state.out*
