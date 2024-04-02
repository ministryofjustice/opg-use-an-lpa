# Use the secondary region

## Summary
In normal conditions, we use the primary region for all our operations. In case of a disaster (i.e. loss of ), we can switch to the secondary region. This runbook describes how to do that.

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

- Commit the changes to the `main` branch and push the changes to the remote repository. The pipeline will run and update the DNS records to point to the secondary region.

- To move back to Ireland, undo the changes to the `terraform.tfvars.json` file, push the changes to the `main` branch and allow the workflow to run.