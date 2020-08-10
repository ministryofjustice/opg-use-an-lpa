#!/usr/bin/env bash

function terraform-compliance { docker run --rm -v $(pwd):/target -i -t eerkunt/terraform-compliance "$@"; }

terraform state pull > tests/state.out
terraform show -json tests/state.out > tests/state.out.json
terraform-compliance -p tests/state.out.json -f tests/


# check diff
# aws-vault exec identity -- terraform plan -out=plan.out
# aws-vault exec identity -- terraform show -json tests/plan.out > tests/plan.out.json
# terraform-compliance -p tests/plan.out.json -f tests/
