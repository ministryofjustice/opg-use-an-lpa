# Generate actor codes

**NOTE:** In order to run this script, the following tools will be needed on a Mac:

- homebrew
- python
- direnv
- aws-vault, with your credentials set up : <https://github.com/99designs/aws-vault>
- you may need to refer to the onboarding instructions for AWS if you do not have an aws identity set up: <https://ministryofjustice.github.io/opg-new-starter/amazon.html>

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
The environment is the first part of the url e.g. demo or ULM222xyz for example.

**IMPORTANT** when generating actor codes, they need to be sent to the requesting person securely, either using:

- Keybase
- Password encrypted zip file, and password sent on a separate communication method.

``` shell
aws-vault exec identity -- python ./generate_actor_codes.py  <environment> <comma separated lpa uids, no spaces>
```

output will look like this

``` log
starting creation task...
arn:aws:ecs:eu-west-1:690083044361:task-definition/production-code-creation:140
arn:aws:ecs:eu-west-1:690083044361:task/5ad44f92-b3d5-449c-aeab-40d9a2ffb4fc
waiting for generation task to start...
Streaming logs for logstream:
timestamp: 1588688690503: message:<json_output>
```

Copy the json_output from the code generator logs output and push it into a file

``` shell
FILENAME=activation_codes_$(date +%Y%m%d)
mkdir -p /tmp/$FILENAME
echo '<json_output>' | jq > /tmp/$FILENAME/$FILENAME.txt
```

Then create an encrypted disk image, copy the file into it and clean up

``` shell
./make_encrypted_image.sh $FILENAME
```

Send the encrypted image to the recipient by email and separately let them know what the password is.
