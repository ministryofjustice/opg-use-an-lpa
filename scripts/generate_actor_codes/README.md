# generate actor codes

This script starts an ECS task to generate actor participant codes.

The script reads account id from environment variables.

You can set these using direnv

``` bash
direnv allow
```

 or by sourcing the .envrc file

``` bash
source .envrc
```

Install python depenencies with pip

``` bash
pip install -r requirements.txt
```

The script uses your IAM user credentials to assume the appropriate role.

You can provide the script credentials using aws-vault

``` bash
aws-vault exec identity -- python ./generate_actor_codes.py  <environment> <comma separated lpa uids>
```
