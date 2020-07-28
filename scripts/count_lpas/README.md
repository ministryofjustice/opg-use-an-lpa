# Count LPAs added between 2 dates

countLPAs does a DynamoDB scan using the aws cli to count LPAs added between 2 dates

If you specify no arguments, you get a count for the last month :
```bash
countLPAs 
```

Or you can provide 2 dates, in valid format, e:g
```bash
countLPAs 2018-01-01 2020-07-09
```

To use aws-vault to run this on a Mac, from within this directory, do for example:
```bash
aws-vault exec ual-dev -- countLPAs
```
or
```bash
aws-vault exec ual-dev -- countLPAs 2018-01-01 2020-07-09
```

or if .  is not in your $PATH, you need to specify exact path e:g 
```bash
aws-vault exec ual-dev -- ./countLPAs
```
or:
```bash
aws-vault exec ual-dev -- /exact/path/to/countLPAs
```

The expected output is something like:
LPAs added between 2018-01-01 and 2020-07-09:
      10
