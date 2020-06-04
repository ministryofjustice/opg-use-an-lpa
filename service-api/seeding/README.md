# Seeding Scripts

## Get Actor Codes

This script can pull actor codes from the legacy code generation tables of an environment. It formats the data to match the lpa-codes api format, and suppliments with data from the lpas-collection api.

The output is a json file that can be used with put_actor_codes.py.

Install python dependencies with pip / pip3 (if you have python 3)

``` shell
pip install -r requirements.txt
```

By default, the script will connect to a local environment. Use `-e` to name an AWS environment to export actor codes from.

You will need to provide AWS credentials if you target an AWS environment. You can provide the script credentials using [aws-vault](https://github.com/99designs/aws-vault).

```bash
aws-vault exec identity -- python get_actor_codes.py -e demo
```

## Put Actor Codes

This script can put actor codes into an lpa-codes api service database. It takes a json file as an input.

The output is a json file that can be used with put_actor_codes.py.

Install python dependencies with pip / pip3 (if you have python 3)

``` shell
pip install -r requirements.txt
```

By default the script will use the actor codes in `./seeding_lpa_codes.json`, and put data into a local running lpa-codes api.

Use `-e` to name an AWS instance of lpa-codes to put data into.

You will need to provide AWS credentials if you target an AWS environment. You can provide the script credentials using [aws-vault](https://github.com/99designs/aws-vault).

Use `-f` to specify a different json file.

Use `-d` is the script will be run inside a docker container. This will set the DynamoDB endpoint url to `http://host.docker.internal:8000`.

```bash
aws-vault exec identity -- python put_actor_codes.py -e int -f /tmp/lpa_codes_demo_2020-06-04.json -d
```
