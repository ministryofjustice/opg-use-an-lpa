# AWS Athena Scripts DynamoDB Exports


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
