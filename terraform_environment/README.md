# Terraform Shared

This terraform configuration manages per-environment resources.

Per-account or otherwise shared resources are managed in `../terraform_shared`

## Namespace resources
It is important to namespace resources to avoid getting errors for creating resources that already exist.

There are two namespace variables available.

`${terraform.workspace}`
returns the workspace name

```
"${terraform.workspace}-use-an-lpa"
``` 
can return `UML-93-use-an-lpa` or `production-use-an-lpa`

`${local.dns_namespace}`
returns the workspace name followed by ., unless the workspace is production where it returns an empty string

```
"viewer.${local.dns_namespace}use-an-lpa"
``` 
can return `viewer.uml-93.use-an-lpa` or `viewer.use-an-lpa`.

## Running Terraform Locally

This repository comes with an `.envrc` file containing useful environment variables for working with this repository.

`.envrc` can be sourced automatically using either [direnv](https://direnv.net) or manually with bash.

```bash
source .envrc
```

```bash
direnv allow
```

This sets environment variables that allow the following commands with no further setup

```bash
aws-vault exe identity -- terraform init
aws-vault exe identity -- terraform plan
aws-vault exe identity -- terraform force-unlock 49b3784c-51eb-668d-ac4b-3bd5b8701925
```