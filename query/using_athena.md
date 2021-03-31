# AWS Athena Scripts DynamoDB Exports


Creating the viewer activity Table

```SQL
CREATE EXTERNAL TABLE IF NOT EXISTS viewer_activity (
         `viewer_code` string,
         `viewed` timestamp
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
         `viewer_code` string,
         `added` timestamp,
         `expires` timestamp,
         `organisation` string,
         `SiriusUid` string,
         `UserLpaActor` string
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
         `Id` string,
         `Email` string,
         `LastLogin` timestamp
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
         `Id` string,
         `ActorId` string,
         `Added` timestamp,
         `SiriusUid` string,
         `UserId` string
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ViewerCodes/AWSDynamoDB/01616672353584-6ff1f666/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
```
