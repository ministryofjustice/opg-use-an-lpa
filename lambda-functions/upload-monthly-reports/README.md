# Upload monthly reports

This lambda runs the monthly reports and writes them to the S3 bucket for analytical platform

### Manually testing the lambda image locally

As the lambda is built from an image, you can manually spin this up locally and test monthly reports against dev environment.

Bring up the lambda locally

```
docker compose build upload-monthly-reports-lambda --no-cache
```

# To test lambda
The curl follows a set command to invoke lambda functions as below

```
curl -XPOST "http://localhost:9008/2015-03-31/functions/function/invocations" -d '{}'
```

# Restart lambda on code changes

Run following to restart the lambda when making code changes as lambda runs in runtime environment and needs
bringing up again for changes to take effect. Note it needs aws-vault as it talks to real AWS

```
aws-vault exec identity docker compose up upload-monthly-reports-lambda
```

# To view logs and output

This is easily done via the Docker Dashboard. If you go to the exec tab you can see the generated results files

# To test lambda in AWS environment

Login in to AWS and search for the required lambda under functions in AWS lambda page.
Choose the required lambda and click test in the test tab.

