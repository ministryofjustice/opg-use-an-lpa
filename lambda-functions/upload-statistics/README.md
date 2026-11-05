# Upload Statistics

The stats table is uploaded with historical stats from csv by a one time run script.

This uploads to the stats table,  the Cloudwatch Metric Statistics about the use of the Use a Lasting Power of Attorney service.

### Manually testing the lambda image locally

MiniStack manages the local Lambda image. Run these commands from the repository root.

By default the script uses a mock JSON response object instead of pulling from actual metrics in stats table/cloudwatch.

Build and start the local environment:

```
make build
make up
```

# To test lambda
```
make lambda_invoke function=upload-statistics
```

# Restart lambda on code changes

Rebuild and redeploy after code changes:

```
make lambda_build && make lambda_deploy
```

# To view logs

```
make lambda_logs function=upload-statistics
```

# To test lambda in AWS environment

Login in to AWS and search for the required lambda under functions in AWS lambda page.
Choose the required lambda and click test in the test tab.

# Including new metrics

New metrics will be included automatically. However if the format of the metric either has dimensions or it is named in
an irregular way with spaces then it may cause the process to fail. In that case you can add the name of the metric
to the ignore list in the `upload_statistics.py` file.
