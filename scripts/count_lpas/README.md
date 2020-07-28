# Count LPAs added between 2 dates

countLPAs does a DynamoDB scan using the aws cli to count LPAs added between 2 dates

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
aws-vault exec ual-dev -- ./countLPAs
```
or:
```bash
aws-vault exec ual-dev -- /exact/path/to/countLPAs
```

The expected output is something like:
LPAs in name-of-env environment added between 2018-01-01 and 2020-07-09:
      10
