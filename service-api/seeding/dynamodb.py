import os
import sys
from decimal import Decimal
import datetime
import botocore
import boto3
import simplejson as json
from passlib.hash import sha256_crypt
from dateutil.relativedelta import relativedelta
import pytz

if 'AWS_ENDPOINT_DYNAMODB' in os.environ:
    # For local development
    dynamodb_endpoint_url = 'http://' + os.environ['AWS_ENDPOINT_DYNAMODB']
    dynamodb = boto3.resource(
        'dynamodb', region_name='eu-west-1', endpoint_url=dynamodb_endpoint_url)

else:

    if os.getenv('CI'):
        role_arn = f"arn:aws:iam::{os.environ['AWS_ACCOUNT_ID']}:role/opg-use-an-lpa-ci"

    else:
        role_arn = f"arn:aws:iam::{os.environ['AWS_ACCOUNT_ID']}:role/operator"

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
endOfToday = timezone.localize(now.replace(
    hour=23, minute=59, second=59, microsecond=0))

lastWeek = endOfToday - datetime.timedelta(days=7)
nextWeek = endOfToday + datetime.timedelta(days=7)
nextYear = endOfToday + datetime.timedelta(days=365)

activateBy = Decimal(nextYear.timestamp())

viewerCodes = [
    {
        'ViewerCode': "P9H8A6MLD3AM",
        'SiriusUid': "700000000138",
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': "806f3720-5b43-49ce-ac66-c670860bf4ee",
        'Comment': 'Seeded data: Valid viewer code'
    },
    {
        'ViewerCode': "JLUPAHNXNKFP",
        'SiriusUid': "700000000138",
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Cancelled': lastWeek.isoformat(),
        'Organisation': "Second Test Organisation",
        'UserLpaActor': "806f3720-5b43-49ce-ac66-c670860bf4ee",
        'Comment': 'Seeded data: Cancelled viewer code',
        'CreatedBy': 23
    },
    {
        'ViewerCode': "N4KBEBEZMNJF",
        'SiriusUid': "700000000138",
        'Expires': lastWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': "806f3720-5b43-49ce-ac66-c670860bf4ee",
        'Comment': 'Seeded data: Expired viewer code'
    },
]

for i in viewerCodes:
    try:
        viewerCodesTable.put_item(
            Item=i,
        )
        response = viewerCodesTable.get_item(
            Key={'ViewerCode': i['ViewerCode']}
        )
        print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))
    except botocore.exceptions.ClientError as error:
        print(error.response['Error']['Code'],
              error.response['Error']['Message'])
        sys.exit(1)

# test user details

actorUsersTable = dynamodb.Table(os.environ['DYNAMODB_TABLE_ACTOR_USERS'])

actorUsers = [
    {
        'Id': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Email': 'opg-use-an-lpa+test-user@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Password': sha256_crypt.hash('umlTest1'),
        'Comment': 'Seeded data: Default test user'
    },
    {
        'Id': 'gb9e7e88-f283-49c6-a79c-65d5d309ef88',
        'Email': 'opg-use-an-lpa+test-user1@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Password': sha256_crypt.hash('umlTest2'),
        'Comment': 'Seeded data: Default test user',
        'NeedsReset': datetime.datetime.now().isoformat()
    }
]

for i in actorUsers:
    try:
        actorUsersTable.put_item(
            Item=i,
        )
        response = actorUsersTable.get_item(
            Key={'Id': i['Id']}
        )
        print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))
    except botocore.exceptions.ClientError as error:
        print(error.response['Error']['Code'],
              error.response['Error']['Message'])
        sys.exit(1)

# added lpas on test user account

userLpaActorMapTable = dynamodb.Table(
    os.environ['DYNAMODB_TABLE_USER_LPA_ACTOR_MAP'])


userLpaActorMap = [
    {
        'Id': '806f3720-5b43-49ce-ac66-c670860bf4ee',
        'SiriusUid': '700000000138',
        'ActorId': 23,
        'Added': '2020-08-19T15:22:32.838097Z ',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'ActivationCode': 'XW34H3HYFDDL',
        'Comment': 'Seeded data'
    },
    {
        'Id': 'f1315df5-b7c3-430a-baa0-9b96cc629648',
        'SiriusUid': '700000000344',
        'ActorId': 59,
        'Added': '2020-08-20T14:37:49.522828Z',
        'ActivatedOn': '2020-08-22T11:44:11.324804Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'ActivationCode': 'WW27H3HYFBBA',
        'Comment': 'Seeded data'
    },
    {
        'Id': '085b6474-d61e-41a4-9778-acb5870c5084',
        'SiriusUid': '700000000047',
        'ActorId': 9,
        'Added': '2021-04-22T15:01:11.548361Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'ActivationCode': 'WWFCCH41R123',
        'Comment': 'Seeded data'
    },
    {
        'Id': 'e69a80db-0001-45a1-a4c5-06bd7ecf8d2e',
        'SiriusUid': '700000000435',
        'ActorId': 78,
        'Added': '2021-04-22T15:01:11.548361Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'ActivateBy': activateBy,
        'Comment': 'Seeded data: Code available to use'
    },
    {
        'Id': '1600be0d-727c-41aa-a9cb-45857a73ba4f',
        'SiriusUid': '700000000252',
        'ActorId': 43,
        'Added': '2021-04-23T11:44:11.324804Z',
        'ActivatedOn': '2021-04-24T11:44:11.324804Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Comment': 'Seeded data'
    }
]

for i in userLpaActorMap:
    try:
        userLpaActorMapTable.put_item(
            Item=i,
        )
        response = userLpaActorMapTable.get_item(
            Key={'Id': i['Id']}
        )
        print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))
    except botocore.exceptions.ClientError as error:
        print(error.response['Error']['Code'],
              error.response['Error']['Message'])
        sys.exit(1)

# added lpas on test user account

statsTable = dynamodb.Table(
    os.environ['DYNAMODB_TABLE_STATS']
)

stats = [
    {
        'TimePeriod': 'Total',
        'lpas_added': 35,
        'lpa_removed_event': 40,
        'account_created_event': 30,
        'account_deleted_event': 30,
        'account_activated_event': 20,
        'viewer_codes_created': 10,
        'viewer_codes_viewed' : 5
    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=4)).strftime('%Y-%m'),
        'lpas_added': 5,
        'lpa_removed_event': 6,
        'account_created_event': 3,
        'account_deleted_event': 2,
        'account_activated_event': 2,
        'viewer_codes_created': 2,
        'viewer_codes_viewed' : 1
    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=3)).strftime('%Y-%m'),
        'lpas_added': 3,
        'lpa_removed_event': 7,
        'account_created_event': 5,
        'account_deleted_event': 1,
        'account_activated_event': 15,
        'viewer_codes_created': 4,
        'viewer_codes_viewed' : 3
    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=2)).strftime('%Y-%m'),
        'lpas_added': 7,
        'lpa_removed_event': 8,
        'account_created_event': 12,
        'account_deleted_event': 3,
        'account_activated_event': 12,
        'viewer_codes_created': 2,
        'viewer_codes_viewed' : 0

    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=1)).strftime('%Y-%m'),
        'lpas_added': 5,
        'lpa_removed_event': 9,
        'account_created_event': 15,
        'account_deleted_event': 1,
        'account_activated_event': 9,
        'viewer_codes_created': 1,
        'viewer_codes_viewed' : 0
    },
    {
        'TimePeriod': datetime.date.today().strftime('%Y-%m'),
        'lpas_added': 15,
        'lpa_removed_event': 10,
        'account_created_event': 6,
        'account_deleted_event': 4,
        'account_activated_event': 12,
        'viewer_codes_created': 1,
        'viewer_codes_viewed' : 1
    }
]

for i in stats:
    try:
        statsTable.put_item(
            Item=i,
        )
        response = statsTable.get_item(
            Key={'TimePeriod': i['TimePeriod']}
        )
        print(json.dumps(response['Item'], indent=4, separators=(',', ': ')))
    except botocore.exceptions.ClientError as error:
        print(error.response['Error']['Code'],
              error.response['Error']['Message'])
        sys.exit(1)
