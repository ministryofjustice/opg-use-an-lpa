# Uploading CSV files to S3 securely for use in Athena

These are the steps to securely upload files to the DynamoDB Exports S3 bucket for use with AWS Athena.

## Prerequisites

You will need to assume the db_analysis role in order to use the dynamodb exports bucket.

## Navigate to S3 in UaL Production

- Log into AWS Console
- Assume the `db-analysis` role in the UaL Production account
- Navigate to the Imported CSVs path in the DyanmoDB exports bucket
https://s3.console.aws.amazon.com/s3/buckets/use-a-lpa-dynamodb-exports-production?region=eu-west-1&prefix=Imported_CSVs/&showversions=false

## Create a Folder with Server Side Encryption

- Click Create Folder
- Folder Name should match your ticket reference
- Enable Server Side Encryption
- For Encryption Type select AWS Key Management Service key (SSE-KMS)
- For AWS KMS key select Choose from your AWS KMS keys
- From the dropdown choose the KMS key with the name `dynamodb-exports-production`
- Click Create Folder

## Upload file with Server Side Encryption

- Click on the folder name to open it
- Click Upload
- Drag and Drop the file or use the add file/add folder options
- Click on Properties to update the Server-side encryption settings
- For Server-side encryption select Specify an encryption key
- For Encryption settings select Override default encryption bucket settings
- For Encryption key type select AWS Key Management Service key (SSE-KMS)
- For AWS KMS key select Choose from your AWS KMS keys
- From the dropdown choose the KMS key with the name `dynamodb-exports-production`
- Then click Upload
