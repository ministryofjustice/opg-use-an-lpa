# Queries using DynamoDB data and Athena

Returns plaintext or json data of accounts matching either an LPA ID or a user's email address.

## Prerequisites

This set of scripts will only work with development environments.


This script will use your AWS credentials to assume the operator role in the development account. Ensure you have permission to assume these roles before running.

Install pip modules

```bash
pip install -r ./requirements.txt
```

## Export Dynamodb data, load Athena

Run the following script to request a DynanmoDB export to S3, drop and re-creation of Athena database and tables, and query against Athena

```shell
aws-vault exec identity -- python ./dynamodb_export.py --environment demo
```

## Script options
TODO TODO
You can check the status of the last dynamo export by running the command again with the `--check_exports` flag

```shell
aws-vault exec identity -- python ./dynamodb_export.py --environment demo --check_exports

Queries will be run for date range 2023-11-01 to 2023-11-30
Waiting for DynamoDb export to be complete ( if run with Athena only option, this is just checking the previous export is complete )
.

DynamoDB export is complete

```

## AWS Athena

The idea going foraward is for the dynamdb_export script to provide regularly run Athena queries. For ad-hoc queries that are one-shot or aren't yet automated, we can access Athena via the AWS Console, and run SQL queries against the ual database that this script creates.

### Querying the Athena tables

Here is an example Select Query for Athena which can be run in the AWS console.

```SQL
-- issues SELECT query

SELECT
    Item.ViewerCode.S as viewercode,
    Item.Added.S as added,
    Item.Expires.S as expires,
    Item.Organisation.S as organisation,
    Item.SiriusUid.S as siriusuid,
    Item.UserLpaActor.S as userlpaactor
FROM viewer_codes
```

More information is available here

<https://docs.aws.amazon.com/athena/latest/ug/ddl-sql-reference.html>
