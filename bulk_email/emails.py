import boto3
from boto3.dynamodb.types import TypeDeserializer

def scan_items(table, limit=1):
    response = table.scan(Limit=limit)  
    if 'Items' in response and response['Items']:
        return response['Items'][0]  # Return the first item
    else:
        return None

# Initialize a session using Amazon DynamoDB
session = boto3.Session()
dynamodb = session.resource('dynamodb')
client = session.client("dynamodb")

# Specify DynamoDB table name
table = dynamodb.Table('2685uml3304-ActorUsers')

# Scan the table for one item
#item = scan_one_item(table)
#while item:
    #print(item['Email'])
    #item = scan_one_item(table)


paginator = client.get_paginator('scan')

response_iterator = paginator.paginate(
    TableName='2685uml3304-ActorUsers'
)

data = []
for page in response_iterator:
    for item in page['Items']:
        print(item['Email']['S'])
