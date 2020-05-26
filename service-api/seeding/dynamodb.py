import os
import boto3
import json
from passlib.hash import sha256_crypt
import datetime

if 'AWS_ENDPOINT_DYNAMODB' in os.environ:
    # For local development
    dynamodb_endpoint_url = 'http://' + os.environ['AWS_ENDPOINT_DYNAMODB']
    dynamodb = boto3.resource(
        'dynamodb', region_name='eu-west-1', endpoint_url=dynamodb_endpoint_url)

else:

    if os.getenv('CI'):
        role_arn = 'arn:aws:iam::{}:role/opg-use-an-lpa-ci'.format(
            os.environ['AWS_ACCOUNT_ID'])
    else:
        role_arn = 'arn:aws:iam::{}:role/operator'.format(
            os.environ['AWS_ACCOUNT_ID'])

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
        'ViewerCode': "P9H8A6MLD3AM",
        'SiriusUid': "700000000138",
        'Expires': "2021-09-12 12:34:56",
        'Added': "2019-01-01 12:34:56",
        'Organisation': "Test Organisation",
    },
    {
        'ViewerCode': "JLUPAHNXNKFP",
        'SiriusUid': "700000000138",
        'Expires': "2021-01-01 12:34:56",
        'Added': "2019-01-01 12:34:56",
        'Organisation': "Test Organisation",
    },
    {
        'ViewerCode': "N4KBEBEZMNJF",
        'SiriusUid': "700000000138",
        'Expires': "2020-01-01 12:34:56",
        'Added': "2019-01-01 12:34:56",
        'Organisation': "Test Organisation",
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

actorLpaCodesTable = dynamodb.Table(
    os.environ['DYNAMODB_TABLE_ACTOR_CODES'])

actorLpaCodes = [
    {
        'ActorCode': "RY4KKKVMRVAK",
        'ActorLpaId': 9,
        'SiriusUid': "700000000047",
        'Active': True,
        'Expires': "2021-09-25T00:00:00Z",
    },
    {
        'ActorCode': "XYUPHWQRECHV",
        'ActorLpaId': 25,
        'SiriusUid': "700000000138",
        'Active': True,
        'Expires': "2021-09-25T00:00:00Z",
    },
    {
        'ActorCode': "JJGWG6JTRLAY",
        'ActorLpaId': 45,
        'SiriusUid': "700000000252",
        'Active': True,
        'Expires': "2021-09-25T00:00:00Z",
    },
    {
        'ActorCode': "MDCKNUA9UMLA",
        'ActorLpaId': 64,
        'SiriusUid': "700000000344",
        'Active': True,
        'Expires': "2018-09-25T00:00:00Z",
    },
    {
        'ActorCode': "MDCKNUA9UMLC",
        'ActorLpaId': 164,
        'SiriusUid': "700000000344",
        'Active': True,
        'Expires': "2018-09-25T00:00:00Z",
    },
]

for i in actorLpaCodes:
    actorLpaCodesTable.put_item(
        Item=i,
    )
    response = actorLpaCodesTable.get_item(
        Key={'ActorCode': i['ActorCode']}
    )
    print(response)
    #print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))

# test user details

actorUsersTable = dynamodb.Table(os.environ['DYNAMODB_TABLE_ACTOR_USERS'])

actorUsers = [
    {
        'Id': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Email': 'opg-use-an-lpa+test-user@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Password': sha256_crypt.hash('umlTest1')
    }
]

for i in actorUsers:
    actorUsersTable.put_item(
        Item=i,
    )
    response = actorUsersTable.get_item(
        Key={'Id': i['Id']}
    )
    print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))
