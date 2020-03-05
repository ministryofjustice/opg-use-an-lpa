# generate actor codes
**NOTE:** In order to run this script, the following tools will be needed on a Mac:
 - homebrew 
 - Python 
 - aws-vault, with your credentials set up : https://github.com/99designs/aws-vault  
 - you may need to refer to the onboarding instructions for AWS if you do not have an aws identity set up:  
  https://ministryofjustice.github.io/opg-new-starter/amazon.html

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

Install python dependencies with pip

``` bash
pip install -r requirements.txt
```

The script uses your IAM user credentials to assume the appropriate role.

You can provide the script credentials using aws-vault

``` bash
aws-vault exec identity -- python ./generate_actor_codes.py  <environment> <comma separated lpa uids>
```
