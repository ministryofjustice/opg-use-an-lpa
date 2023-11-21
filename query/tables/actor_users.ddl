CREATE EXTERNAL TABLE IF NOT EXISTS actor_users (
    Item struct <Id:struct<S:string>,
                  Email:struct<S:string>,
                  LastLogin:struct<S:date>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ActorUsers/AWSDynamoDB/01699371668200-7b5d140b/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
