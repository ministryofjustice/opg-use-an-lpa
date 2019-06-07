import os
import boto3
import json
import pprint

# Setup the DynamoDB client.

# cluster_config = {
#    cluster_name = "${aws_ecs_cluster.use-an-lpa.name}"
#    account_id = "${local.account_id}"
# }

if 'AWS_ENDPOINT_DYNAMODB' in os.environ:
    # For local development
    dynamodb_endpoint_url = 'http://' + os.environ['AWS_ENDPOINT_DYNAMODB']
    dynamodb = boto3.resource('dynamodb', region_name='eu-west-1', endpoint_url=dynamodb_endpoint_url)

    table = dynamodb.Table(os.environ['DYNAMODB_TABLE_VIEWER_CODES'])
else:
    with open('/tmp/cluster_config.json') as json_file:
        parameters = json.load(json_file)
        pprint.pprint(parameters)

    if os.getenv('CI'):
        role_arn = 'arn:aws:iam::{}:role/ci'.format(parameters['account_id'])
    else:
        role_arn = 'arn:aws:iam::{}:role/account-write'.format(parameters['account_id'])

    # Create a authenticated client
    dynamodb = boto3.resource(
        'dynamodb',
        region_name='eu-west-1',
        # aws_access_key_id=session['Credentials']['AccessKeyId'],
        # aws_secret_access_key=session['Credentials']['SecretAccessKey'],
        # aws_session_token=session['Credentials']['SessionToken']
    )

    table = dynamodb.Table(parameters['viewer_codes_table'])


# tables = dynamodb.list_tables()
# print(json.dumps(tables, indent=4, separators=(',', ': ')))


table.put_item(
   Item={
        'ViewerCode': "123456789012",
        'SiriusId': "12345678901",
        'Expires': "2020-01-01 12:34:56",
    }
)

table.put_item(
   Item={
        'ViewerCode': "987654321098",
        'SiriusId': "98765432109",
        'Expires': "2020-01-01 12:34:56",
    }
)

table.put_item(
   Item={
        'ViewerCode': "222222222222",
        'SiriusId': "22222222222",
        'Expires': "2019-01-01 12:34:56",
    }
)

# Scan and output the table, so we can see what we've got in the logs.
response = table.scan()
for i in response['Items']:
    print(json.dumps(i, indent=4, separators=(',', ': ')))
