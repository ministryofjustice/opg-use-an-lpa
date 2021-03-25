# Queries using DynamoDB data

Returns plaintext or json data of accounts matching either an LPA ID or a user's email address.

## Prerequisites

This set of scripts will only work with development environments.


This script will use your AWS credentials to assume the operator role in the development account. Ensure you have permission to assume these roles before running.

Install pip modules

```bash
pip install -r ./requirements.txt
```

## Export and Pull Dynamodb Data

Run the following script to request a DynanmoDB export to S3

```shell
aws-vault exec identity -- python ./dynamodb_export.py --environment demo

DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
exporting tables
         IN_PROGRESS /demo-ActorCodes/AWSDynamoDB/01616690444920-5a52a238/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorUsers
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
exporting tables
         IN_PROGRESS /demo-ActorUsers/AWSDynamoDB/01616690445236-d5abeeaa/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
exporting tables
         IN_PROGRESS /demo-ViewerCodes/AWSDynamoDB/01616690445522-725d68c1/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerActivity
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
exporting tables
         IN_PROGRESS /demo-ViewerActivity/AWSDynamoDB/01616690445816-f937defd/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-UserLpaActorMap
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
exporting tables
         IN_PROGRESS /demo-UserLpaActorMap/AWSDynamoDB/01616690446054-4cefee64/data/
```

You can check sthe status of the last export by running the command again with the `--check_exports` flag

```shell
aws-vault exec identity -- python ./dynamodb_export.py --environment demo --check_exports

DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED /demo-ActorCodes/AWSDynamoDB/01616672948566-2bd6cda2/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ActorUsers
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED /demo-ActorUsers/AWSDynamoDB/01616672948854-f7585baa/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerCodes
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED /demo-ViewerCodes/AWSDynamoDB/01616672949229-89913a34/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-ViewerActivity
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED /demo-ViewerActivity/AWSDynamoDB/01616672949494-747f6af2/data/


DynamoDB Table ARN: arn:aws:dynamodb:eu-west-1:367815980639:table/demo-UserLpaActorMap
S3 Bucket Name: use-a-lpa-dynamodb-exports-development
         COMPLETED /demo-UserLpaActorMap/AWSDynamoDB/01616672949780-0dfc333e/data/
```

Once the exports are completed you can pull the S3 objects using AWS CLI and the S3 Sync command.

To pull s3 objects locally run

```shell
aws-vault exec ual-dev -- aws s3 sync s3://use-a-lpa-dynamodb-exports-development/ ./s3_objects
```

## Pandas and JSON Line Data

The load_s3_exports.py script reads the dynamodb data which is in gzipped JSON Lines format. More info at <https://jsonlines.org/>.

At this time, the data is loaded into a Pandas dataframe.
