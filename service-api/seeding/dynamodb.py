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
        "SiriusUid": "700000000526",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "NYGUAMNB46JQ",
        "ActorLpaId": "97"
    },
    {
        "SiriusUid": "700000000138",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "8EFXFEF48WJ4",
        "ActorLpaId": "23"
    },
    {
        "SiriusUid": "700000000526",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "3JHKF3C6D9W8",
        "ActorLpaId": "92"
    },
    {
        "SiriusUid": "700000000435",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "CXAY6GPCQ4X3",
        "ActorLpaId": "76"
    },
    {
        "SiriusUid": "700000000617",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "EEWNGCGW6LWU",
        "ActorLpaId": "115"
    },
    {
        "SiriusUid": "700000000138",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "QRTXRCFRLK46",
        "ActorLpaId": "2588"
    },
    {
        "SiriusUid": "700000000047",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "E9YRUTPM6RBW",
        "ActorLpaId": "7"
    },
    {
        "SiriusUid": "700000000047",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "6HFCLATAGLEY",
        "ActorLpaId": "12"
    },
    {
        "SiriusUid": "700000000617",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "W7D4MT7HAQEH",
        "ActorLpaId": "110"
    },
    {
        "SiriusUid": "700000000344",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "JU3CJMJK89YG",
        "ActorLpaId": "64"
    },
    {
        "SiriusUid": "700000000138",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "4UAL33PEQNAY",
        "ActorLpaId": "25"
    },
    {
        "SiriusUid": "700000000047",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "3YKAJNDD8P3N",
        "ActorLpaId": "9"
    },
    {
        "SiriusUid": "700000000344",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "6CFKNNFLPCP4",
        "ActorLpaId": "59"
    },
    {
        "SiriusUid": "700000000252",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "RQ3W8G4EYRQJ",
        "ActorLpaId": "45"
    },
    {
        "SiriusUid": "700000000435",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "44THDVFJ4P4Y",
        "ActorLpaId": "78"
    },
    {
        "SiriusUid": "700000000252",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "XW34H3HYFDDL",
        "ActorLpaId": "43"
    },
    {
        "SiriusUid": "700000000344",
        "Active": True,
        "Expires": "2021-06-22T23:59:59+00:00",
        "ActorCode": "PEYBDGT6AJ7U",
        "ActorLpaId": "3022"
    }
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

# added lpas on test user account

userLpaActorMapTable = dynamodb.Table(os.environ['DYNAMODB_TABLE_USER_LPA_ACTOR_MAP'])

userLpaActorMap = [
    {
        'Id': '806f3720-5b43-49ce-ac66-c670860bf4ee',
        'SiriusUid': '700000000138',
        'ActorId': '23',
        'Added': '2020-08-19T15:22:32.838097Z',
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
