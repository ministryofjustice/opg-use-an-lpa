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
