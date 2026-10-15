CREATE EXTERNAL TABLE IF NOT EXISTS user_lpa_actor_map (
    Item struct <Id:struct<S:string>,
                ActorId:struct<S:string>,
                SiriusUid:struct<S:string>,
                UserId:struct<S:string>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-UserLpaActorMap/AWSDynamoDB/00000000000000-00000000/data/'
TBLPROPERTIES ('has_encrypted_data'='true');

