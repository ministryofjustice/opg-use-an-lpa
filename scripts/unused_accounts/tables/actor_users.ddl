CREATE EXTERNAL TABLE IF NOT EXISTS actor_users (
    Item struct <Id:struct<S:string>,
                  Identity:struct<S:string>,
                  Email:struct<S:string>,
                  LastLogin:struct<S:string>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ActorUsers/AWSDynamoDB/00000000000000-00000000/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
