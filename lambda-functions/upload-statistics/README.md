# Upload Statistics

The stats table is uploaded with historical stats from csv by a one time run script.

This uploads to the stats table,  the Cloudwatch Metric Statistics about the use of the Use a Lasting Power of Attorney service.

### Manually testing the lambda image locally

As the lambda is built from an image, you can manually spin this up locally and test the statistic uploads against dev environment.

By default the script uses a mock JSON response object instead of pulling from actual metrics in stats table/cloudwatch.

Bring up the lambda locally

```
make build_all
```

# To test lambda
The curl follows a set command to invoke lambda functions as below

```
curl -XPOST "http://localhost:9007/2015-03-31/functions/function/invocations" -d '{}'
```

# Restart lambda on code changes

Run following to restart the lambda when making code changes as lambda runs in runtime environment and needs
bringing up again for changes to take effect.

```
make up upload-stats-lambda
```

# To view logs

```
make logs upload-stats-lambda
```

# To test lambda in AWS environment

Login in to AWS and search for the required lambda under functions in AWS lambda page.
Choose the required lambda and click test in the test tab.

# Including new metrics

New metrics will be included automatically. However if the format of the metric either has dimensions or it is named in
an irregular way with spaces then it may cause the process to fail. In that case you can add the name of the metric
to the ignore list in the `upload-statistics.py` file.
