# Event Receiver

This Lambda function will process events from the new Event Receiver EventBus which processes events from MLPAB.

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

# To test lambda running locally within localstack

The containers should be up and running.

Start the localstack logs :
```
make logs localstack
```

The lambda can be run with :
```
make emit_lpa_access_granted_event lpaId=4UX3
```
"4UX3" being the file name of the json within "mock-integrations/lpa-data-store/src/lpas/4UX3.json"

The resulting output can then be seen in localstack logs

A successful response (will not include any error logging) should give output resembling this :

```
START RequestId: 837f31b2-cdcf-4e11-b810-33b402263df0 Version: $LATEST
localstack  | 2025-03-06T10:44:23.229102137Z 2025-03-06T10:44:23.228 DEBUG --- [et.reactor-2] l.s.l.i.version_manager    : [event-receiver-lambda-837f31b2-cdcf-4e11-b810-33b402263df0] 
END RequestId: 837f31b2-cdcf-4e11-b810-33b402263df0
localstack  | 2025-03-06T10:44:23.229119262Z 2025-03-06T10:44:23.229 DEBUG --- [et.reactor-2] l.s.l.i.version_manager    : [event-receiver-lambda-837f31b2-cdcf-4e11-b810-33b402263df0] 
REPORT RequestId: 837f31b2-cdcf-4e11-b810-33b402263df0     
Duration: 70.81 ms      Billed Duration: 71 ms  Memory Size: 128 MB 
```

# Making changes to the lambda
After making any changes to the lambda, a re-build will be needed for the changes to be applied

Bring localstack down first
```
make down
```
Build the container again
```
make build localstack
```
Bring up the container
```
make up localstack
```