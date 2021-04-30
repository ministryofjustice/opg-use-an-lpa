import os
import boto3
import json
from passlib.hash import sha256_crypt
import datetime
import pytz

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

now = datetime.datetime.now()
timezone = pytz.timezone("Europe/London")
endOfToday = timezone.localize(now.replace(hour=23, minute=59, second=59, microsecond=0))

lastWeek = endOfToday - datetime.timedelta(days=7)
nextWeek = endOfToday + datetime.timedelta(days=7)

viewerCodes = [
    {
        'ViewerCode': "P9H8A6MLD3AM",
        'SiriusUid': "700000000138",
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': "806f3720-5b43-49ce-ac66-c670860bf4ee",
    },
    {
        'ViewerCode': "JLUPAHNXNKFP",
        'SiriusUid': "700000000138",
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Cancelled': lastWeek.isoformat(),
        'Organisation': "Second Test Organisation",
        'UserLpaActor': "806f3720-5b43-49ce-ac66-c670860bf4ee",
    },
    {
        'ViewerCode': "N4KBEBEZMNJF",
        'SiriusUid': "700000000138",
        'Expires': lastWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': "806f3720-5b43-49ce-ac66-c670860bf4ee",
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

# added lpas on test user account

userLpaActorMapTable = dynamodb.Table(
    os.environ['DYNAMODB_TABLE_USER_LPA_ACTOR_MAP'])

userLpaActorMap = [
    {
        'Id': '806f3720-5b43-49ce-ac66-c670860bf4ee',
        'SiriusUid': '700000000138',
        'ActorId': '23',
        'Added': '2020-08-19T15:22:32.838097Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77'
    },
    {
        'Id': 'f1315df5-b7c3-430a-baa0-9b96cc629648',
        'SiriusUid': '700000000344',
        'ActorId': '59',
        'Added': '2020-08-20T14:37:49.522828Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77'
    },
    {
            'Id': '085b6474-d61e-41a4-9778-acb5870c5084',
            'SiriusUid': '700000000047',
            'ActorId': '9',
            'Added': '2021-04-22T15:01:11.548361Z',
            'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77'
    },
    {
        'Id': '1600be0d-727c-41aa-a9cb-45857a73ba4f',
        'SiriusUid': '700000000252',
        'ActorId': '43',
        'Added': '2021-04-23T11:44:11.324804Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77'
    }
]

for i in userLpaActorMap:
    userLpaActorMapTable.put_item(
        Item=i,
    )
    response = userLpaActorMapTable.get_item(
        Key={'Id': i['Id']}
    )
    print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))
