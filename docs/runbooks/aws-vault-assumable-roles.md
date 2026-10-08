# Configuring aws-vault with assumable roles

You will need aws-vault installed. You will also need the base profile `identity` set up.

See [OPG Technical Guidance](https://docs.opg.service.justice.gov.uk/documentation/get_started.html#5-set-up-aws-vault) for information on how to install and configure aws-vault.

## Set up role

Create named profiles for aws-vault by manually adding them to your aws config file.

```shell
vi ~/.aws/config
```

Add the following block

```ini
## Use an LPA
[profile ual-dev]
region=eu-west-1
role_arn=arn:aws:iam::367815980639:role/operator
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<AWS.USERNAME>

[profile ual-preprod]
region=eu-west-1
role_arn=arn:aws:iam::888228022356:role/operator
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<AWS.USERNAME>

[profile ual-prod]
region=eu-west-1
role_arn=arn:aws:iam::690083044361:role/operator
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<AWS.USERNAME>

[profile management]
region=eu-west-1
role_arn=arn:aws:iam::311462405659:role/operator
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<AWS.USERNAME>
```

Once this is done you will be able to see your new profile available for use

```shell
aws-vault list
```

## Using the profiles

Use `ual-dev`, `ual-preprod`, `ual-prod`, or `management` profiles for AWS CLI commands that act directly on resources in that environment, such as ECS commands.

Run Terraform as `identity`. Terraform assumes roles in the environment, management, identity, and backup accounts. Running it as a `ual-*` profile fails because that role cannot assume the required roles.

```shell
aws-vault exec identity -- terraform workspace select <workspace-name>
```
