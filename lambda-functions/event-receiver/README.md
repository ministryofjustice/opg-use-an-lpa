# Event Receiver

This Lambda function will process events from the new Event Receiver EventBus which processes events from MLPAB.

MiniStack deploys the function as a local arm64 image and invokes it through
EventBridge and SQS. Use `make lambda_build && make lambda_deploy` after changes and
`make lambda_logs function=event-receiver` for its logs. See the root README for setup.

### Testing the lambda image locally

As the lambda is built from an image, this can be spun up to test in dev environment.

Bring up the lambda locally.

```
make build
```

Start the containers
```
make up
```

# To test the lambda running locally within MiniStack

The containers should be up and running.

View the Lambda logs after sending an event:
```
make lambda_logs function=event-receiver
```

Send an event through EventBridge with:
```
make emit_event eventType=lpa-access-granted payload=4UX3.json
```

`eventType` is the fixture folder name under `mock-integrations/events`;
`payload` selects the JSON file within it. This example selects
`mock-integrations/events/lpa-access-granted/4UX3.json`.

The target publishes to the default EventBridge bus and matching rules route the event
to `event-bus-queue`, the event-source mapping (setup in ministack-init.sh)
invokes the Lambda.

# Making changes to the lambda
Rebuild and redeploy the local images after code changes:

```
make lambda_build && make lambda_deploy
```
