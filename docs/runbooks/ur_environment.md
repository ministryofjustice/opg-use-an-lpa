# User Research environment

## Background

The User Research environment is used to test the application with users. When deploying, there is an option to make the environment publicly accessible. This is useful when testing with users who are not part of the MOJ.

## Deploying to the User Research environment

Select Run Workflow on the  [Deploy Branch to User Research Environment](https://github.com/ministryofjustice/opg-use-an-lpa/actions/workflows/workflow-deploy-ref-to-env.yml). Enter the branch name that you want to deploy and select Enable public access to the environment if you want to make the environment publicly accessible. Leave the Terraform workspace as default (ur). Click Run Workflow.

## Enabling or disabling public access to the User Research environment

Follow the steps in [Deploying to the User Research environment](#deploying-to-the-user-research-environment) and select Enable public access to the environment or deselect it to disable public access to the environment. Ensure that the Terraform workspace is set to ur and that the branch name is set to whatever is currently deployed. Click Run Workflow.

## Accessing the User Research environment

The User Research environment is accessible at https://ur.use-lasting-power-of-attorney.service.gov.uk/home. If public access is disabled, you will need to be on the MOJ VPN to access the environment. If public access is enabled, you will not need to be on the MOJ VPN to access the environment.

## Deleting the User Research environment

There is no automatic way to delete the User Research environment. If you want to delete the environment, you will need to do it manually via the use of the Terraform CLI.

### Prerequisites

Ensure you have the following installed:

- [Terraform CLI](https://learn.hashicorp.com/tutorials/terraform/install-cli)
- [aws-vault](https://docs.opg.service.justice.gov.uk/documentation/get_started.html#5-set-up-aws-vault)

### To delete the User Research environment

1. Open a terminal and navigate to the root of the opg-use-an-lpa repository.
2. If you do not use direnv, run `source .envrc` to load the environment variables.
3. Run `aws-vault exec identity -- terraform init` to initialise the Terraform workspace.
4. Run `aws-vault exec identity -- terraform workspace select ur` to select the User Research workspace.
5. Run `aws-vault exec identity -- terraform destroy` to delete the User Research environment.
6. When prompted, enter `yes` to confirm the deletion.
