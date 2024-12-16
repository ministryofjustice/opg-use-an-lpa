# Event Receiver

This Lambda function will process events from the new Event Receiver EventBus which processes events from MLPAB.

### Testing the lambda image locally

As the lambda is built from an image, this can be spun up to test in dev environment.

Bring up the lambda locally

```
make build
```

# To test lambda
The curl follows a set command to invoke lambda functions as below

```
curl -XPOST "http://localhost:9008/2015-03-31/functions/function/invocations" -d '{}'
```
