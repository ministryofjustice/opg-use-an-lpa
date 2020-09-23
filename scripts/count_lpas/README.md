# Count LPAs added between 2 dates

countLPAs does a DynamoDB scan using the aws cli to count Use a Lasting power of Attorney service statistics between 2 dates

If you specify no arguments, you get a count for the last month , for demo environment:

```bash
countLPAs
```

You can provide the environment name explicitly e:g

```bash
countLPAs demo
```

If you specifiy the environment, you can also provide 2 dates, in valid format, e:g

```bash
countLPAs demo 2018-01-01 2020-07-09
```

To use aws-vault to run this on a Mac, from within this directory, do for example:

```bash
aws-vault exec ual-dev -- ./countLPAs demo 2018-01-01 2020-07-09
```

or:

```bash
aws-vault exec ual-dev -- /exact/path/to/countLPAs demo 2018-01-01 2020-07-09
```

The expected output is something like:

```text
LPAs in name-of-env environment added between 2020-07-17 and 2020-07-31:
     672
Viewer codes created in name-of-env environment between 2020-07-17 and 2020-07-31:
     362
Viewer codes viewed in name-of-env environment between 2020-07-17 and 2020-07-31:
     356
Actor accounts created in name-of-env environment between 2020-07-17 and 2020-08-11:
2029
```


## Notes

The `Actor` accounts count can fail due to large number of data points - you will get an error like:

```text
An error occurred (InvalidParameterCombination) when calling the GetMetricStatistics operation: You have requested up to 1,608 datapoints, which exceeds the limit of 1,440. You may reduce the datapoints requested by increasing Period, or decreasing the time range.
```

Reduce the date range and run again
