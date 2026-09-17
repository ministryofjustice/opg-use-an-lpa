# Manage Maintenance Mode

This script will enable or disable maintenance mode for a targeted environment.

**Note:** this script supports **demo**, **ur**, **preproduction**, and **production**. Development and PR environments use shared load balancers and are not supported.

## Setup

You will need to set up assumable roles in aws-vault. Follow the instructions at [aws-vault-assumable-roles](../aws-vault-assumable-roles.md).

Run the script from this directory:

```bash
cd docs/runbooks/maintenance_mode
```

### Usage

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
aws-vault exec ual-dev -- ./manage_maintenance.sh \
  --environment demo \
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
