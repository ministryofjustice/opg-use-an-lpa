# Generate actor codes

**NOTE:** In order to run this script, the following tools will be needed on a Mac:

- homebrew
- python
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

you now have 2 options. you can do:

- **CSV file code generation**: for larger batches.
- **Inline code generation**: useful for a quick generation of a small number of codes.

for both options, the `<environment>` is the first part of the url e.g. demo or ULM222xyz for example.

**Running from a CSV file**: This option allows you to take a CSV file with an LPA code on each line similar to this:

``` text
700000000000
700000000001
..etc
```

We do some sanitising of the file to remove unexpected characters, dashes etc,
However, it is worth checking the file before running.

Command:

``` shell
./generate_actor_codes.sh -e <environment> -f </path/to/lpacodes.csv>
```

**Running inline**: This option is simply to allow inline running of the codes.

command:

``` shell
./generate_actor_codes.sh -e <environment> -i "<comma separated lpa uids>"
```

**Note:** The list of `comma separated lpa uids` must be surrounded by double quotes.

Both options have:

- `-v` option to switch on debug mode.
- `-n` option to keep the output files after disk generation, useful for inspection of the files
- `-c` option to to just validate the codes only.

You will be given the chance to review the input, and cancel if needed.

Note: `${FILENAME}` prefix is in the `bash` date based format `<environment>_activation_codes_$(date +%Y%m%d%H%M)`.

``` log
environment name=<environment>
LPA Id's=<comma separated lpa uids, no spaces>
Total unique LPAs: <no of LPA uids - corresponding to the list count above>
A new ${FILENAME}.txt will be generated.
This will be stored securely in disk image ${FILENAME}.dmg and copied to your Documents folder.
Are the above details correct? [y/n]:
```

hitting `y` will start the process off.
You will see a log similar to below, which will validate the lpa list 1 by 1 , which might take a few minutes:

``` log
checking LPA validity...
700000000001 is valid
700000000002 is valid
700000000003 is valid
...
```

If an LPA ID is not found in the validation step it will report something similar to `700000000000 is invalid. returned {}.`
if there are errors at the end of the validation step, the script will abort: `aborted, due to the above errors in red.`

- Correct the file as needed
- rerun the script, then you should see this after the prompt.

``` log
LPA's all valid!
generating actor codes...
starting creation task...
arn:aws:ecs:eu-west-1:000000000000:task-definition/<environment>-code-creation:1
arn:aws:ecs:eu-west-1:000000000000:task/5ad44f92-b3d5-449c-aeab-40d9a2ffb4fc
waiting for generation task to start...
Streaming logs for logstream: xxxxxxx
timestamp: 0000000000000: message:<json_output>
task completed.
Sanity check the logs...
extracting and formatting LPA codes...
Sanity check the final output...
/tmp/${FILENAME}/${FILENAME}.txt generated.
Contents for checking:
<json_output_reformatted>
```

The script will then prompt for and re-enter a password to create the disk image.

This is copied to your `Documents` folder in a disk image named `${FILENAME}.dmg`.

Then:

- Send the encrypted image to the recipient by Slack or Keybase.
- Separately, let them know what the password is, e.g. via email.
