import os
import boto3
import json

#dynamodb = boto3.resource('dynamodb', region_name='eu-west-1', endpoint_url="http://host.docker.internal:4569")
dynamodb = boto3.resource('dynamodb', region_name='eu-west-1')

table = dynamodb.Table(os.environ['DYNAMODB_TABLE_VIEWER_CODES'])

response = table.put_item(
   Item={
        'ViewerCode': "123456789012",
        'SiriusId': "12345678901",
        'Expires': "2020-01-01 12:34:56",
    }
)

print("PutItem succeeded:")
print(json.dumps(response, indent=4))
