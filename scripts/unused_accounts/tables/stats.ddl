CREATE EXTERNAL TABLE IF NOT EXISTS stats (
    Item struct <TimePeriod:struct<S:string>,
                AccountActivatedEvent:struct<S:string>,
                AccountCreatedEvent:struct<S:string>,
                AccountDeletedEvent:struct<S:string>,
                AddedLpaTypeHwEvent:struct<S:string>,
                AddedLpaTypePfaEvent:struct<S:string>,
                DownloadSummaryEvent:struct<S:string>,
                FullMatchKeyRequestSuccessLpaTypeHwEvent:struct<S:string>,
                FullMatchKeyRequestSuccessLpaTypePfaEvent:struct<S:string>,
                LpaRemovedEvent:struct<S:string>,
                LpasAdded:struct<S:string>,
                OlderLpaNeedsCleansingEvent:struct<S:string>,
                UserAbroadAddressRequestSuccessEvent:struct<S:string>,
                ViewLpaShareCodeCancelledEvent:struct<S:string>,
                ViewLpaShareCodeExpiredEvent:struct<S:string>,
                ViewerCodesCreated:struct<S:string>,
                ViewerCodesViewed:struct<S:string>>
)
ROW FORMAT SERDE 'org.openx.data.jsonserde.JsonSerDe'
WITH SERDEPROPERTIES (
         'serialization.format' = '1' )
LOCATION 's3://use-a-lpa-dynamodb-exports-development/demo-stats/AWSDynamoDB/01699371668200-7b5d140b/data/'
TBLPROPERTIES ('has_encrypted_data'='true');
