# Looking up details of Access Codes from LPA-Codes DynamoDB

You will need aws-vault installed. You will also need the base profile `identity` set up.

See https://ministryofjustice.github.io/opg-new-starter/amazon.html#using-aws-vault-with-your-account-in-opg-identity for information on how to install and configure aws-vault.

## Set up the Sirius Prod role

Create named profiles for aws-vault by manually adding them to your aws config file.

```shell
vi ~/.aws/config
```

Add the following block to your config file.

```ini
[profile sirius-prod]
region=eu-west-1
role_arn=arn:aws:iam::649098267436:role/breakglass
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/awsusername
```

After the profile is added you can use it to make a get-item request on the lpa-codes-production dynamodb table.

```shell
aws-vault exec sirius-prod -- aws dynamodb get-item --table-name lpa-codes-production --key '{"code": {"S": "CODE_FROM_CUSTOMER"}}'

{
    "Item": {
        "active": {
            "BOOL": false
        },
        "code": {
            "S": "CODE_FROM_CUSTOMER"
        },
        "last_updated_date": {
            "S": "2021-07-30"
        },
        "status_details": {
            "S": "Revoked"
        },
        "expiry_date": {
            "N": "1656003389"
        },
        "dob": {
            "S": "1982-03-18"
        },
        "generated_date": {
            "S": "2021-06-23"
        },
        "actor": {
            "S": "700000000001"
        },
        "lpa": {
            "S": "700000000000"
        }
    }
}
```
