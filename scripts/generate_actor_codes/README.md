# Generate actor codes

**NOTE:** In order to run this script, the following tools will be needed on a Mac:

- homebrew
- python
- direnv
- awk
- jq: <https://stedolan.github.io/jq/>
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

run the following shell script.
The environment is the first part of the url e.g. demo or ULM222xyz for example.

``` shell
./generate_actor_codes.sh <environment> <comma separated lpa uids>
```

You will be given the chance to review the input, and cancel if needed.

Note: `${FILENAME}` prefix is in the `bash` date based format `<environment>_activation_codes_$(date +%Y%m%d%H%M)`.

``` log
environment name=<environment>
LPA Id's=<comma separated lpa uids, no spaces>
environment name=357automateac
A new ${FILENAME}.txt will be generated.
This will be stored securely in disk image ${FILENAME}.dmg and copied to your Documents folder.
Are the above details correct? [y/n]:
```

hitting `y` will start the process off. You will see a log similar to below:

``` log
generating actor codes...
starting creation task...
arn:aws:ecs:eu-west-1:000000000000:task-definition/<environment>-code-creation:1
arn:aws:ecs:eu-west-1:000000000000:task/5ad44f92-b3d5-449c-aeab-40d9a2ffb4fc
waiting for generation task to start...
Streaming logs for logstream:
timestamp: 0000000000000: message:<json_output>
task completed.
Sanity check the logs...
extracting and formatting LPA codes...
Sanity check the final output...
/tmp/${FILENAME}/${FILENAME}.txt generated.
Contents for checking:
<json_output_reformatted>
removing intermediate file...
```

The script will then prompt for and re-enter a password to create the disk image.

This is copied to your `Documents` folder in a disk image named `${FILENAME}.dmg`.

Then:

- Send the encrypted image to the recipient by Slack or Keybase.
- Separately, let them know what the password is, e.g. via email.
