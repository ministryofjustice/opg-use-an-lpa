# Deactivate Actor Codes

Updates all actor codes in the lpa-codes integration service linked to a Sirius LPA case number to inactive.

A JSON file lists the LPA case numbers.

## Prerequisites

This script will use your AWS credentials to assume the operator or other appropriate role in non-production accounts. Ensure you have permission to assume these roles before running.

Install pip modules

```bash
pip install ../pipeline/requirements.txt
```

## Running the script

If a match is made to an LPA case number in the JSON file list, the status will be updated to False to deactivate it.

The script will print the before and after status of the data.

If no matches are made, nothing is printed.

If Dynamodb update_item conditions are not met, the update will print a ConditionalCheckFailedException message and the script will move on to the next item.

Conditions are;

- The code is currently active
- The status of the code is "Generated"

```bash
aws-vault exec identity -- python ./deactivate_actor_codes.py

{
    "Item": {
        "active": {
            "BOOL": true
        },
        "code": {
            "S": "ABCDEF123456"
        },
        "last_updated_date": {
            "S": "2020-06-05"
        },
        "status_details": {
            "S": "Imported"
        },
        "expiry_date": {
            "N": "1618703999"
        },
        "dob": {
            "S": "1985-06-12"
        },
        "generated_date": {
            "S": "2020-06-05"
        },
        "actor": {
            "S": "700000000014"
        },
        "lpa": {
            "S": "700000000013"
        }
    },
    "ResponseMetadata": {
        "HTTPStatusCode": 200,
        "RetryAttempts": 0
    }
}
{
    "Attributes": {
        "active": {
            "BOOL": true
        },
        "code": {
            "S": "ABCDEF123456"
        },
        "last_updated_date": {
            "S": "2020-06-05"
        },
        "status_details": {
            "S": "Imported"
        },
        "expiry_date": {
            "N": "1618703999"
        },
        "dob": {
            "S": "1985-06-12"
        },
        "generated_date": {
            "S": "2020-06-05"
        },
        "actor": {
            "S": "700000000014"
        },
        "lpa": {
            "S": "700000000013"
        }
    },
    "ResponseMetadata": {
        "HTTPStatusCode": 200,
        "RetryAttempts": 0
    }
}
```

## Usage

```bash
aws-vault exec identity -- python ./deactivate_actor_codes.py -h
usage: deactivate_actor_codes.py [-h] [-e E] [-f F] [-r R]

Put actor codes into the lpa-codes API service.

optional arguments:
  -h, --help  show this help message and exit
  -e E        The environment to dactive actor codes in.
  -f F        Path to the json file listing LPAs.
  -r R        IAM role name to assume when pushing actor codes.
```
