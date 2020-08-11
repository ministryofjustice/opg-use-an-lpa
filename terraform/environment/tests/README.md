# Testing Infrasctructure as Code with Terraform-Compliance

Terraform-Compliance is a BDD testing framework for Terraform IaC.

Information and examples on using Terraform-Compliance available here <https://terraform-compliance.com/>.

First, pull the terraform-compliance docker image

``` bash
docker pull eerkunt/terraform-compliance
```

## Run script

The commands to setup and run terraform-compliance have been set up in a script.

``` bash
cd terraform/environment
aws-vault exec identity -- tests/terraform-compliance.sh
```
