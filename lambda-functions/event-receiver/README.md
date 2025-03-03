# Event Receiver

This Lambda function will process events from the new Event Receiver EventBus which processes events from MLPAB.

### Testing the lambda image locally

As the lambda is built from an image, this can be spun up to test in dev environment.

Bring up the lambda locally.

```
make build
```

# To test lambda from docker compose container
The curl follows a set command to invoke lambda functions as below

```
curl -XPOST "http://localhost:9008/2015-03-31/functions/function/invocations" -d '{}'
```

# To test lambda running locally within localstack

The lambda can be run with :
```
awslocal lambda invoke --function-name event-receiver-lambda --payload '{}' output.txt
```

The resulting output can then be seen in cloudwatch .
Firstly get the log-stream with :

```
awslocal logs describe-log-streams --log-group-name /aws/lambda/event-receiver-lambda
```

This should give output resembling this :

```
{
    "logStreams": [
        {
            "logStreamName": "2025/02/05/[$LATEST]ad2e302e7df4278b9ed945acb6c94658",
            "creationTime": 1738770222226,
            "firstEventTimestamp": 1738770222172,
            "lastEventTimestamp": 1738770222205,
            "lastIngestionTime": 1738770222230,
            "uploadSequenceToken": "1",
            "arn": "arn:aws:logs:eu-west-1:000000000000:log-group:/aws/lambda/event-receiver-lambda:log-stream:2025/02/05/[$LATEST]ad2e302e7df4278b9ed945acb6c94658",
            "storedBytes": 264
        }
    ]
}
```

Then using the log-stream-name seen in the output from that command, query that exact logstream with e:g

```
awslocal logs get-log-events --log-group-name /aws/lambda/event-receiver-lambda --log-stream-name '2025/02/04/[$LATEST]b65407922be0d35a97caccdc55ec3dff'
```

This should give output resembling this :

```
{
    "events": [
        {
            "timestamp": 1738770222172,
            "message": "START RequestId: 38e5a7f3-1740-452d-9ec9-f1db28d07c1e Version: $LATEST",
            "ingestionTime": 1738770222230
        },
        {
            "timestamp": 1738770222183,
            "message": "done",
            "ingestionTime": 1738770222230
        },
        {
            "timestamp": 1738770222194,
            "message": "END RequestId: 38e5a7f3-1740-452d-9ec9-f1db28d07c1e",
            "ingestionTime": 1738770222230
        },
        {
            "timestamp": 1738770222205,
            "message": "REPORT RequestId: 38e5a7f3-1740-452d-9ec9-f1db28d07c1e\tDuration: 0.89 ms\tBilled Duration: 1 ms\tMemory Size: 128 MB\tMax Memory Used: 128 MB\t",
            "ingestionTime": 1738770222230
        }
    ],
    "nextForwardToken": "f/00000000000000000000000000000000000000000000000000000003",
    "nextBackwardToken": "b/00000000000000000000000000000000000000000000000000000000"
}
```
