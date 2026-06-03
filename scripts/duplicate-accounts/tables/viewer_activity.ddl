CREATE EXTERNAL TABLE IF NOT EXISTS viewer_activity (
    Item struct <ViewerCode:struct<S:string>,
                 ViewedBy:struct<S:string>,
                 Viewed:struct<S:date>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-ViewerActivity/AWSDynamoDB/01616672353743-e52c5c67/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
