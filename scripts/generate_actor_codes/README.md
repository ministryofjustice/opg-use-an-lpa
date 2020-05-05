# generate actor codes
**NOTE:** In order to run this script, the following tools will be needed on a Mac:
 - homebrew
 - python
 - direnv
 - aws-vault, with your credentials set up : https://github.com/99designs/aws-vault
 - you may need to refer to the onboarding instructions for AWS if you do not have an aws identity set up:
  https://ministryofjustice.github.io/opg-new-starter/amazon.html

This script starts an ECS task to generate Actor participant codes.

The script reads account id from environment variables.
You will need to change the `.envrc` file to the appropriate account id for prod / pre-prod or development environments.

You can set these using direnv

``` shell
direnv allow
```

 or by sourcing the `.envrc` file

``` shell
source .envrc
```

Install python dependencies with pip / pip3 (if you have python 3)

``` shell
pip install -r requirements.txt
```
or
```shell
pip3 install -r requirements.txt
```
The script uses your IAM user credentials to assume the appropriate role.

You can provide the script credentials using aws-vault.
note environment is the first part of the url e.g. demo or ULM222xyz for example.

``` shell
aws-vault exec identity -- python ./generate_actor_codes.py  <environment> <comma separated lpa uids>
```
**NOTE** when generating actor codes, they need to be sent to the requesting person securely, either using:
- Keybase
- Password encrypted zip file, and password sent on a separate communication method.
