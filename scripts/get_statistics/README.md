# Get Statistics

Returns json data from DynamoDB and Cloudwatch Metric Statistics about the use of the Use a Lasting Power of Attorney service.

Without specifying arguments, this script will assume a role into the production account and print statistics for every month from the launch of the service until today.

```bash
aws-vault exec identity -- python ./get_accounts_created_metric.py | jq
```

```json
{
  "statistics": {
    "accounts_created": {
      "total": 14805,
      "monthly": {
        "2020-07-01": 1231,
        "2020-08-01": 4583,
        "2020-09-01": 5308,
        "2020-10-01": 3683
      }
    },
    "lpas_added": {
      "total": 6913,
      "monthly": {
        "2020-07-01": 394,
        "2020-08-01": 2081,
        "2020-09-01": 2549,
        "2020-10-01": 1889
      }
    },
    "viewer_codes_created": {
      "total": 5002,
      "monthly": {
        "2020-07-01": 569,
        "2020-08-01": 1251,
        "2020-09-01": 1868,
        "2020-10-01": 1314
      }
    },
    "viewer_codes_viewed": {
      "total": 5787,
      "monthly": {
        "2020-07-01": 858,
        "2020-08-01": 1248,
        "2020-09-01": 2218,
        "2020-10-01": 1463
      }
    }
  }
}
```

Arguments can be used to specify start and end dates (which will be used to set a full month), and the environment to get statistics for.

```bash
aws-vault exec identity -- python ./get_statistics.py -h
usage: get_statistics.py [-h] [--environment ENVIRONMENT]
                         [--startdate STARTDATE] [--enddate ENDDATE]

Print the total accounts created. Starts from teh first of the month of the
given start date.

optional arguments:
  -h, --help            show this help message and exit
  --environment ENVIRONMENT
                        The environment to provide stats for
  --startdate STARTDATE
                        Where to start metric summing, defaults to launch of
                        service
  --enddate ENDDATE     Where to end metric summing, defaults to today
```

## Testing

Making the calls to AWS services to collect data can take a while, so while working on the output of the script, a json file can be used instead.

Redirect the output of the get_statistics.py script to a json file, then use the `--test` argument to read from the file.

```bash
aws-vault exec identity -- python ./get_statistics.py > output.json
aws-vault exec identity -- python ./get_statistics.py --test
aws-vault exec identity -- python ./get_statistics.py --text --test
```
