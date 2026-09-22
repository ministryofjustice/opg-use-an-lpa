# Manage Maintenance Mode

This runbook enables maintenance mode for a targeted environment, then optionally scales all ECS services down to zero.

**Note:** this script supports **demo**, **ur**, **preproduction**, and **production**. Development (PR) environments use shared load balancers and are not supported.

## Why optionally disable compute?

Maintenance mode only serves a static page for incoming traffic on the ALBs. The ECS services
continue running and may still process requests.

- **Guaranteed no writes**, e.g. before a data migration or restore
- **Security incident containment**, reducing the blast radius if a container is compromised
- **Cost savings** during extended maintenance windows

## Setup

You will need to set up assumable roles in aws-vault. Follow the instructions at [aws-vault-assumable-roles](../aws-vault-assumable-roles.md).

### Usage

Navigate to the maintenance mode folder:

```bash
cd docs/runbooks/maintenance_mode
```

Use `ual-dev` for `demo` and `ur`, `ual-preprod` for `preproduction`, and `ual-prod` with breakglass access for `production`.

AWS CLI output may open in a pager, press `q` to exit it each time so the script can continue running.

To turn on maintenance mode for both the use and view front ends:

``` bash

aws-vault exec ual-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --maintenance_mode
```

To turn off maintenance mode for both the use and view front ends:

``` bash
aws-vault exec ual-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --disable_maintenance_mode
```

To turn on maintenance mode for just one front end, use `--front_end view` or `--front_end use`

``` bash
aws-vault exec ual-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --front_end view \
  --maintenance_mode
```

To turn off maintenance mode for one front end:

```bash
aws-vault exec ual-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --front_end view \
  --disable_maintenance_mode
```

### Verification

Check the affected English and Welsh URLs:

| Environment | Use | View |
| --- | --- | --- |
| `demo` | [English](https://demo.use-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://demo.use-lasting-power-of-attorney.service.gov.uk/cy/home) | [English](https://demo.view-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://demo.view-lasting-power-of-attorney.service.gov.uk/cy/home) |
| `ur` | [English](https://ur.use-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://ur.use-lasting-power-of-attorney.service.gov.uk/cy/home) | [English](https://ur.view-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://ur.view-lasting-power-of-attorney.service.gov.uk/cy/home) |
| `preproduction` | [English](https://preproduction.use-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://preproduction.use-lasting-power-of-attorney.service.gov.uk/cy/home) | [English](https://preproduction.view-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://preproduction.view-lasting-power-of-attorney.service.gov.uk/cy/home) |
| `production` | [English](https://use-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://use-lasting-power-of-attorney.service.gov.uk/cy/home) | [English](https://view-lasting-power-of-attorney.service.gov.uk/home) / [Welsh](https://view-lasting-power-of-attorney.service.gov.uk/cy/home) |

## Optional Scale compute to zero

Once maintenance mode has been enabled, compute can be scaled down to zero via 2 possible methods.

### Option 1: via the CLI (recommended)

Sets the desired task count to zero for all services.

**Note:** Omit `mock-onelogin-service` for production.

```bash
for service in actor-service viewer-service api-service pdf-service admin-service mock-onelogin-service; do
  aws-vault exec <profile> -- aws ecs update-service \
    --cluster <environment>-use-an-lpa \
    --service $service \
    --desired-count 0
done
```

Check that every service's running and desired count is `0`:

```bash
aws-vault exec <profile> -- aws ecs describe-services \
  --cluster <environment>-use-an-lpa \
  --services actor-service viewer-service api-service pdf-service admin-service mock-onelogin-service \
  --query 'services[].{name:serviceName,desired:desiredCount,running:runningCount}'
```

### Option 2: via Terraform

`mock_onelogin` (non-production only) is not driven by autoscaling and has no
terraform-managed way to scale to zero; use the Option 1 CLI command for it.

1. Edit [terraform.tfvars.json](../../../terraform/environment/terraform.tfvars.json) and set
`minimum` and `maximum` to `0` for every service under
`environments.<environment>.autoscaling`: `api`, `pdf`, `use`, `view`, `admin`.

2. Navigate to `terraform/environment/` and select the environment's workspace:

  ```bash
  aws-vault exec identity -- terraform workspace select <environment>
  ```

3. For `production`, open `terraform/environment/.envrc`, change
  `TF_VAR_default_role=operator` to `TF_VAR_default_role=breakglass`, and run
  `direnv allow` from that directory.
4. Plan the terraform and carefully inspect the plan before applying:

  ```bash
  aws-vault exec identity -- terraform plan
  ```

  ```bash
  aws-vault exec identity -- terraform apply
  ```

Alternatively, commit the change on a branch, open a pull request and let the pipeline
handle the deploy.

### Reverting

1. If Option 1 (CLI) was used, set each service's desired count back to its
   `minimum` value for that environment under `environments.<environment>.autoscaling` in
   [terraform.tfvars.json](../../../terraform/environment/terraform.tfvars.json)
   (`mock-onelogin-service` is `1` if `mock_onelogin_enabled` is `true`)

   ```bash
   aws-vault exec <profile> -- aws ecs update-service --cluster <environment>-use-an-lpa --service <service-name> --desired-count <minimum>
   ```

   Run this once per service, substituting `<service-name>` and its `<minimum>`.
2. If Option 2 (Terraform) was used, revert the `terraform.tfvars.json` change and apply
  locally or via the pipeline.
3. Follow the maintenance mode usage above to turn off maintenance mode.
