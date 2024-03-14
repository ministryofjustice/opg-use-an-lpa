CREATE EXTERNAL TABLE IF NOT EXISTS actor_codes (
    Item struct <ActorCode:struct<S:string>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ActorCodes/AWSDynamoDB/01616672353584-6ff1f666/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
