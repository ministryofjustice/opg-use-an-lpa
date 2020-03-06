# generate actor codes
**NOTE:** In order to run this script, the following tools will be needed on a Mac:
 - homebrew 
 - python 
 - direnv
 - aws-vault, with your credentials set up : https://github.com/99designs/aws-vault  
 - you may need to refer to the onboarding instructions for AWS if you do not have an aws identity set up:  
  https://ministryofjustice.github.io/opg-new-starter/amazon.html
 - it may also be helpful to use zsh (oh my zsh) installed into ITerm2 if you're feeling adventurous!

This script starts an ECS task to generate actor participant codes.

The script reads account id from environment variables.

You can set these using direnv

``` shell script
direnv allow
```

 or by sourcing the .envrc file

``` shell script
source .envrc
```

Install python dependencies with pip

``` shell script
pip install -r requirements.txt
```

The script uses your IAM user credentials to assume the appropriate role.

You can provide the script credentials using aws-vault. 
note environment is the first part of the url e.g. demo or ULM222xyz for example.

``` shell script
aws-vault exec identity -- python ./generate_actor_codes.py  <environment> <comma separated lpa uids>
```
