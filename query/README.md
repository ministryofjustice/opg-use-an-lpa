# Queries using DynamoDB data

Returns plaintext or json data of accounts matching either an LPA ID or a user's email address.

## Prerequisites

This set of scripts will only work with development environments.


This script will use your AWS credentials to assume the operator role in the development account. Ensure you have permission to assume these roles before running.

Install pip modules

```bash
pip install -r ./requirements.txt
```

## Export Dynamodb data

Run the following script to request a DynanmoDB export to S3

```shell
aws-vault exec identity -- python ./dynamodb_export.py --environment demo

DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         IN_PROGRESS s3://use-a-lpa-dynamodb-exports-development/demo-ActorCodes/AWSDynamoDB/01617269934602-162d6355/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorUsers
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         IN_PROGRESS s3://use-a-lpa-dynamodb-exports-development/demo-ActorUsers/AWSDynamoDB/01617269934827-b65efa47/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         IN_PROGRESS s3://use-a-lpa-dynamodb-exports-development/demo-ViewerCodes/AWSDynamoDB/01617269935076-45fe3523/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerActivity
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         IN_PROGRESS s3://use-a-lpa-dynamodb-exports-development/demo-ViewerActivity/AWSDynamoDB/01617269935310-6968000e/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-UserLpaActorMap
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         IN_PROGRESS s3://use-a-lpa-dynamodb-exports-development/demo-UserLpaActorMap/AWSDynamoDB/01617269935547-9e1e9b04/data/
```

You can check sthe status of the last export by running the command again with the `--check_exports` flag

```shell
aws-vault exec identity -- python ./dynamodb_export.py --environment demo --check_exports

DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED s3://use-a-lpa-dynamodb-exports-development/demo-ActorCodes/AWSDynamoDB/01617269934602-162d6355/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorUsers
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED s3://use-a-lpa-dynamodb-exports-development/demo-ActorUsers/AWSDynamoDB/01617269934827-b65efa47/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED s3://use-a-lpa-dynamodb-exports-development/demo-ViewerCodes/AWSDynamoDB/01617269935076-45fe3523/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerActivity
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED s3://use-a-lpa-dynamodb-exports-development/demo-ViewerActivity/AWSDynamoDB/01617269935310-6968000e/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-UserLpaActorMap
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED s3://use-a-lpa-dynamodb-exports-development/demo-UserLpaActorMap/AWSDynamoDB/01617269935547-9e1e9b04/data/
```

## AWS Athena

We can use AWS Athena to create a database and tables of the exported DynamoDB data so that we can use SQL to further explore and query our data.

### Getting started

See the getting started guide and follow Step 1: Creating a Database here <https://docs.aws.amazon.com/athena/latest/ug/getting-started.html>

After this you are able to write and run queries. Queries can be used to create tables.

### Creating tables

Here are some example SQL statements for creating tables from each DynamoDB Export

Note:

- the location for each export can be copied from the out put of the dynamodb_export.p script
- These queries create tables if they don't already exists. If the query is changed to add some new data, either delete and recreate the table or use an UPDATE query.

Examples:

Creating the viewer activity Table

```SQL
CREATE EXTERNAL TABLE IF NOT EXISTS viewer_activity (
    Item struct <ViewerCode:struct<S:string>,
                 Viewed:struct<S:date>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ViewerActivity/AWSDynamoDB/01616672353743-e52c5c67/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
```

Creating the viewer codes Table

```SQL
CREATE EXTERNAL TABLE IF NOT EXISTS viewer_codes (
    Item struct <ViewerCode:struct<S:string>,
                  Added:struct<S:date>,
                  Expires:struct<S:date>,
                  Organisation:struct<S:string>,
                  SiriusUid:struct<S:string>,
                  UserLpaActor:struct<S:string>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ViewerCodes/AWSDynamoDB/01616672353584-6ff1f666/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
```

Creating the actor users Table

```SQL
CREATE EXTERNAL TABLE IF NOT EXISTS actor_users (
    Item struct <Id:struct<S:string>,
                  Email:struct<S:string>,
                  LastLogin:struct<S:date>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ViewerCodes/AWSDynamoDB/01616672353584-6ff1f666/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
```

Creating the user-lpa-actor map Table

```SQL
CREATE EXTERNAL TABLE IF NOT EXISTS user_lpa_actor_map (
    Item struct <Id:struct<S:string>,
                ActorId:struct<S:string>,
                Added:struct<S:date>,
                SiriusUid:struct<S:string>,
                UserId:struct<S:string>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ViewerCodes/AWSDynamoDB/01616672353584-6ff1f666/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
```

### Querying the newly created tables

After creating tables, you can run queries. Here is an example Select Query for Athena.

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
