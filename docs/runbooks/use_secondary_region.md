# Use the secondary region

## Summary
In normal conditions, we use the primary region for all our operations. In case of a disaster (i.e. loss of ECS), we can switch to the secondary region. This runbook describes how to do that.

## Prerequisites
- AWS Vault installed and configured with the correct credentials for production breakglass
- Terraform installed
- The opg-use-an-lpa repository cloned locally

## Steps

- In the opg-use-an-lpa repository, open the `terraform/environment/terraform.tfvars.json` file

Change the object in `environments.production.regions.eu-west-1` to the below:
```
        "eu-west-1": {
          "enabled": true,
          "name": "eu-west-1",
          "is_active": false,
          "is_primary": true
        }
```

and change the object in `environments.production.regions.eu-west-2` to the below:
```
        "eu-west-2": {
          "enabled": true,
          "name": "eu-west-2",
          "is_active": true,
          "is_primary": false
        }
```

Changing the `is_active` flag will ensure that the DNS records are updated to point to the secondary region and ensure compute resources are created in the secondary region (i.e. the ECS services will have their desired count set to 0 in the primary region and at least 3 in the secondary region).

Ensure that the `is_primary` flag is **NOT** changed. This is used to determine where the DynamoDB tables are created. They are replicated to the secondary region automatically.

## Via Terraform

If you are running the disaster recovery locally via Terraform, you need to select the production workspace and then assume the breakglass role.

Navigate to `terraform/environment/` in your terminal and use the command `aws-vault exec identity -- terraform workspace select production`.

Open the `terraform/environment/.envrc` file. Change `TF_VAR_default_role=operator` to `TF_VAR_default_role=breakglass` and then run either `source .envrc` or `direnv allow` from the `terraform/environment/` directory within your terminal. The other roles do not need to be changed.

Next, use the command `aws-vault exec identity -- terraform plan` and inspect the plan output and ensure resources are being created in eu-west-2.

Once you're happy, use the command `aws-vault exec identity -- terraform apply`.

## Via Pipeline

Alternatively, you can let the pipeline do this. Commit the changes on a PR branch and push create a pull request. Once the pull request pipeline has passed and the PR has the relevant approvals, merge this into the main branch.

The pipeline will run and update the DNS records to point to the secondary region.

## Moving back to eu-west-1

To move back to Ireland, undo the changes to the `terraform.tfvars.json` file, push the changes to the `main` branch and allow the workflow to run, or via Terraform apply.
