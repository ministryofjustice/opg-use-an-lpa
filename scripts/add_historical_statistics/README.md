# Add Historical Statistics
This script is used to migrate statistics from the spreadsheet maintained for metrics of eventcodes
to the dynamodb Stats table.

## Useage
There is a .vscode/launch.json to allow developers to run against their local environment in VSCode

The code can also be run from the terminal by using 

    aws-vault exec ual-dev -- go run main.go -table=1991uml2674-Stats

optional parameters can be used to allow a json file to be written

    aws-vault exec ual-dev -- go run main.go -table=1991uml2674-Stats -outputJson=true -jsonFilename=test.json


## Caveats
Some data was edited from the original stats spreadsheet to correctly line up with the AWS event codes.
For this reason I have uploaded the CSV file that was converted from the stats spreadsheet.

The total value has also changed to reflect the fact we only want the total to calculate up to the last month in the stats table