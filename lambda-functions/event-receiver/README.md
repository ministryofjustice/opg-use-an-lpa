# Event Receiver

The stats table is uploaded with historical stats from csv by a one time run script.

This uploads to the stats table,  the Cloudwatch Metric Statistics about the use of the Use a Lasting Power of Attorney service.

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