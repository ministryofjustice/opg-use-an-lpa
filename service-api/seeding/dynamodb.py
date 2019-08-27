import os
import boto3
import json

if 'AWS_ENDPOINT_DYNAMODB' in os.environ:
    # For local development
    dynamodb_endpoint_url = 'http://' + os.environ['AWS_ENDPOINT_DYNAMODB']
    dynamodb = boto3.resource('dynamodb', region_name='eu-west-1', endpoint_url=dynamodb_endpoint_url)

else:

    if os.getenv('CI'):
        role_arn = 'arn:aws:iam::{}:role/ci'.format(os.environ['AWS_ACCOUNT_ID'])
    else:
        role_arn = 'arn:aws:iam::{}:role/account-write'.format(os.environ['AWS_ACCOUNT_ID'])

    # Get a auth token
    session = boto3.client(
        'sts',
        region_name='eu-west-1',
    ).assume_role(
        RoleArn=role_arn,
        RoleSessionName='db_seeding',
        DurationSeconds=900
    )

    # Create a authenticated client
    dynamodb = boto3.resource(
        'dynamodb',
        region_name='eu-west-1',
        aws_access_key_id=session['Credentials']['AccessKeyId'],
        aws_secret_access_key=session['Credentials']['SecretAccessKey'],
        aws_session_token=session['Credentials']['SessionToken']
    )

viewerCodesTable = dynamodb.Table(os.environ['DYNAMODB_TABLE_VIEWER_CODES'])

viewerCodes = [
    {
        'ViewerCode': "123456789012",
        'SiriusId': "123456789012",
        'Expires': "2020-01-01 12:34:56",
    },
    {
        'ViewerCode': "987654321098",
        'SiriusId': "987654321098",
        'Expires': "2020-01-01 12:34:56",
    },
    {
        'ViewerCode': "222222222222",
        'SiriusId': "222222222222",
        'Expires': "2019-01-01 12:34:56",
    },
]

for i in viewerCodes:
    viewerCodesTable.put_item(
       Item=i,
    )
    response = viewerCodesTable.get_item(
        Key={'ViewerCode': i['ViewerCode']}
    )
    print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))

actorLpaCodesTable = dynamodb.Table(os.environ['DYNAMODB_TABLE_ACTOR_LPA_CODES'])

actorLpaCodes = [
    {
        'ActorLpaCode': "123456789012",
    },
]

for i in actorLpaCodes:
    actorLpaCodesTable.put_item(
       Item=i,
    )
    response = actorLpaCodesTable.get_item(
        Key={'ActorLpaCode': i['ActorLpaCode']}
    )
    print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))
