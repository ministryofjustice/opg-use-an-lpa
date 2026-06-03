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
