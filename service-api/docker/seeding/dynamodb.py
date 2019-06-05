import os
import boto3
import json

"""
This file MUST only perform idempotent actions.
It will be executed *at least* once per deploy, put potentially more than once.
It will not be run in production.
"""

if 'AWS_ENDPOINT_DYNAMODB' in os.environ:
    # For local development
    dynamodb_endpoint_url = os.environ['AWS_ENDPOINT_DYNAMODB']
else:
    # Should be none in AWS
    dynamodb_endpoint_url = None

dynamodb = boto3.resource('dynamodb', region_name='eu-west-1', endpoint_url=dynamodb_endpoint_url)

table = dynamodb.Table(os.environ['DYNAMODB_TABLE_VIEWER_CODES'])

response = table.put_item(
   Item={
        'ViewerCode': "123456789012",
        'SiriusId': "12345678901",
        'Expires': "2020-01-01 12:34:56",
    }
)
print(json.dumps(response, indent=4))

response = table.put_item(
   Item={
        'ViewerCode': "987654321098",
        'SiriusId': "98765432109",
        'Expires': "2020-01-01 12:34:56",
    }
)
print(json.dumps(response, indent=4))

response = table.put_item(
   Item={
        'ViewerCode': "222222222222",
        'SiriusId': "22222222222",
        'Expires': "2019-01-01 12:34:56",
    }
)
print(json.dumps(response, indent=4))

print("Seeding finished")
