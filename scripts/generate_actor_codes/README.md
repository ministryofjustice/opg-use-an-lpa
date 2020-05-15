# Generate actor codes

**NOTE:** In order to run this script, the following tools will be needed on a Mac:

- homebrew
- python
- direnv
- jq: https://stedolan.github.io/jq/
- aws-vault, with your credentials set up : <https://github.com/99designs/aws-vault>
- you may need to refer to the onboarding instructions for AWS if you do not have an aws identity set up: <https://ministryofjustice.github.io/opg-new-starter/amazon.html>

This script starts an ECS task to generate Actor participant codes.

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

``` shell
aws-vault exec identity -- python ./generate_actor_codes.py <environment> <comma separated lpa uids, no spaces>
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

Copy the `<json_output>` from the code generator logs output and create the encrypted file

``` shell
echo "<json_output>" | ./make_encrypted_image.sh <optional_path_to_write_dmg:default $HOME/Desktop>
```

You'll be prompted for a password to set

## Optional

Please mount the image (double click and enter password) and verify the `codes.txt` file has the following:
- All Sirius case numbers should be split with hyphen into 3 groups of 4. e.g `7xxx-xxxx-xxxx`
- All actor codes should be split with a space into 3 groups of 4. e.g `ABC1 D2E3 FG4H`

## Finally

Send the encrypted image to the recipient by slack or keybase and separately let them know what the password is, e.g. via email.
