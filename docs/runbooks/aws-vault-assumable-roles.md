# Configuring aws-vault with assumable roles

You will need aws-vault installed. You will also need the base profile `identity` set up.

See https://ministryofjustice.github.io/opg-new-starter/amazon.html#using-aws-vault-with-your-account-in-opg-identity for information on how to install and configure aws-vault.

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
```

Once this is done you will be able to see your new profile available for use

```shell
aws-vault list
```
