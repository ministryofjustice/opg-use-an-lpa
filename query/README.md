# Queries using DynamoDB data and Athena

Returns plaintext or json data of accounts matching either an LPA ID or a user's email address.

## Prerequisites

This set of scripts will only work with development environments.


This script will use your AWS credentials to assume the operator role in the development account. Ensure you have permission to assume these roles before running.

Install pip modules

```bash
pip install -r ./requirements.txt
```

## Running in Virtual Env

If there are issues installing the above requirements, it may be needed to use virtual env to intall and use the script
```shell
virtualenv venv --python=python3.12 ; source venv/bin/activate
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

### Useful queries

#### Query to check duplicate account user login in id's and LPA information

Here is the Select query to get the duplicate Id's, email and count of Lpa's for the duplicate user accounts.

```SQL
SELECT 
    a.Item.id.S as User_Id,
    a.Item.identity.S as One_Login_Id,
    a.Item.email.S as User_Email,
    COUNT(b.Item.SiriusUid) AS Lpa_Count 
FROM actor_users a LEFT JOIN user_lpa_actor_map b ON a.Item.id.S = b.Item.UserId.S 
WHERE a.Item.email.S IN 
    (SELECT Item.email.S FROM actor_users GROUP BY Item.email.S HAVING COUNT(*) > 1) 
GROUP BY a.Item.email.S, a.Item.id.S, a.Item.identity.S 
ORDER BY a.Item.email.S
```

Here is the Select query to get the duplicate Id's, email and the Lpa's for the duplicate user accounts.

```SQL
SELECT  
    a.Item.id.S as User_Id, 
    a.Item.email.S as User_Email,
    b.Item.SiriusUid.S as Lpa_Id 
FROM actor_users a LEFT JOIN user_lpa_actor_map b ON a.Item.id.S = b.Item.UserId.S 
WHERE a.Item.email.S IN (SELECT Item.email.S FROM actor_users GROUP BY Item.email.S HAVING COUNT(*) > 1) 
GROUP BY a. Item.email.S, a.Item.id.S,b.Item.SiriusUid.S 
ORDER BY a.Item.email.S
```
