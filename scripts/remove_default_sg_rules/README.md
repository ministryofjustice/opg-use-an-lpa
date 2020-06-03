# Check AWS ECR scan results

This script retrieves a list of all AWS regions available, then iterates over each removing ingress rules from the default security group found there.

## Install python dependencies with pip

``` bash
pip install -r requirements.txt
```

## Run script

The script uses your IAM user credentials to assume the appropriate role.

You can provide the script credentials using aws-vault

``` bash
aws-vault exec identity -- python scripts/remove_default_sg_rules/remove_default_sg_rules.py \
  --account_id 123456789012 \
  --role_name operator
```
