# Guide for searching through logs in AWS

This is a guide for how to search/filter CloudWatch logs for the Use a Lasting Power of Attorney service.
It contains useful queries for finding specific log data, in order to quickly and efficiently respond to issues on the service or for a specific user.

To begin searching log data, go to CloudWatch --> Logs --> Insights

At the top of the page, select the log group that you want to perform queries on

Enter the queries below and click "Run query"

The order of filters does matter!

## Useful filtering snippets
Specific log streams eg actor-app, viewer etc
```
| filter @logStream like /(?i)(actor-app)/
```

Parsing the log message into data categories
```
| parse @message '* - [*] "* * *" * * *' as ip, datetime, httpverb, path, protocol, responsecode, bytes, logmessage
```

Having parsed the data, we can now filter the logs by those categories, such as respond code shown below:
```
| filter responsecode like /(?i)(4\d\d)/
```

Displaying the fields that you want to see:
```
| DISPLAY datetime, responsecode, logmessage
```

If you want to find log data for a particular user that has reported an issue such as not being able to add their LPA, you can filter for certain log messages found in the code, for example:
```
| filter @message like /(?i)(Validating code <code> is inactive)/
```

Feel free to add to this list if there are any other filters that you have found useful :)
