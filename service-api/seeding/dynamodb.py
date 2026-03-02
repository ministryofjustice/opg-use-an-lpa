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
import base64
import hashlib

def with_identity(user):
    user['Identity'] = 'urn:fdc:mock-one-login:2023:' + base64.b64encode(hashlib.sha256(user['Email'].encode('utf-8')).digest()).decode("utf-8")
    return user

if 'AWS_ENDPOINT_DYNAMODB' in os.environ:
    # For local development
    dynamodb = boto3.resource(
        'dynamodb', region_name='eu-west-1', endpoint_url=os.environ['AWS_ENDPOINT_DYNAMODB'])

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
twoWeeks = endOfToday + datetime.timedelta(days=14)
nextYear = endOfToday + datetime.timedelta(days=365)

activateBy = Decimal(nextYear.timestamp())


# Duplicate account testing
actorUserDuplicate1aID = '55dc3ed8-5d37-1714-f89b-b4d40d56656a'
actorUserDuplicate1bID = '4dc2230e-e669-28c4-cfe3-82c9b3480d3b'
actorUserDuplicate2aID = '2f686aa5-1af4-5d34-3f1b-e096cf897ebe'
actorUserDuplicate2bID = '584f9052-ee61-4d34-f96b-933c4e9564f2'
actorUserDuplicate3aID = '1271831e-e033-cbd4-c613-fb3c20275137'
actorUserDuplicate3bID = '7a54b602-dd95-efd4-732b-15838f6abe72'
actorUserDuplicate4aID = '23347331-45f5-3ee4-dec3-bdbca5e8dddf'
actorUserDuplicate4bID = '46fb2189-627d-45a4-75d3-c89cbb136e70'
actorUserDuplicate5aID = '2751fdf0-9efe-0424-fb4b-0de8d91634e7'
actorUserDuplicate5bID = 'ede7af9a-59ee-6904-dab3-798a9c23bf55'

userLpaActorDuplicate2aID = '0840234c-456d-40e0-af01-48abbf584ded'
userLpaActorDuplicate3aID = '71737efb-1819-fe44-28e3-3d86700eadd8'
userLpaActorDuplicate4aID = 'e556934e-7f84-0824-d7db-c0017dce74da'
userLpaActorDuplicate4bID = '4bc3bfb9-f3f7-9d24-97e3-cd4df809dc9c'

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
    {
        'ViewerCode': "N4KBEBEZMNJF",
        'SiriusUid': '700000000047',
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': '085b6474-d61e-41a4-9778-acb5870c5084',
        'Comment': 'Seeded data: Valid viewer code'
    },
    {
        'ViewerCode': "AAAAAAAAAAAA",
        'SiriusUid': '700000000047',
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate2aID,
        'Comment': 'Seeded data: Valid viewer code'
    },
    {
        'ViewerCode': "BBBBBBBBBBBB",
        'SiriusUid': '700000000138',
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate3aID,
        'Comment': 'Seeded data: Valid viewer code'
    },
    {
        'ViewerCode': "CCCCCCCCCCC1",
        'SiriusUid': '700000000047',
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate4aID,
        'Comment': 'Seeded data: Valid viewer code'
    },
    {
        'ViewerCode': "CCCCCCCCCCC2",
        'SiriusUid': '700000000047',
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Cancelled': lastWeek.isoformat(),
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate4aID,
        'Comment': 'Seeded data: Cancelled viewer code'
    },
    {
        'ViewerCode': "CCCCCCCCCCC3",
        'SiriusUid': '700000000047',
        'Expires': lastWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate4aID,
        'Comment': 'Seeded data: Cancelled viewer code'
    },
    {
        'ViewerCode': "DDDDDDDDDDD1",
        'SiriusUid': '700000000047',
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate4bID,
        'Comment': 'Seeded data: Valid viewer code'
    },
    {
        'ViewerCode': "DDDDDDDDDDD2",
        'SiriusUid': '700000000047',
        'Expires': nextWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Cancelled': lastWeek.isoformat(),
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate4bID,
        'Comment': 'Seeded data: Cancelled viewer code'
    },
    {
        'ViewerCode': "DDDDDDDDDDD3",
        'SiriusUid': '700000000047',
        'Expires': lastWeek.isoformat(),
        'Added': "2019-01-01T12:34:56.123456Z",
        'Organisation': "Test Organisation",
        'UserLpaActor': userLpaActorDuplicate4bID,
        'Comment': 'Seeded data: Cancelled viewer code'
    },
]

# The following loop was used to test UML-3582. It can be uncommented if any further issues
# arise and/or testing required

#for i in range(33):
#    viewerCodes.append(
#    {
#            'ViewerCode': f"P9H8A6MLD3{str(i+20)}",
#            'SiriusUid': "700000000138",
#            'Expires': nextWeek.isoformat(),
#            'Added': "2019-01-01T12:34:56.123456Z",
#            'Organisation': "Test Organisation",
#            'UserLpaActor': "806f3720-5b43-49ce-ac66-c670860bf4ee",
#            'Comment': 'Seeded data: Valid viewer code'
#    })

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
    with_identity({
        'Id': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Email': 'opg-use-an-lpa+test-user@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Password': sha256_crypt.hash('umlTest1'),
        'Comment': 'Seeded data: Default test user'
    }),
    {
        'Id': 'gb9e7e88-f283-49c6-a79c-65d5d309ef88',
        'Email': 'opg-use-an-lpa+test-user1@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Password': sha256_crypt.hash('umlTest2'),
        'Comment': 'Seeded data: Default test user',
        'NeedsReset': datetime.datetime.now().isoformat()
    },
    with_identity({
        'Id': actorUserDuplicate1aID,
        'Email': 'opg-use-an-lpa+duplicate-1@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate with an LPA)',
    }),
    with_identity({
        'Id': actorUserDuplicate1bID,
        'Email': 'opg-use-an-lpa+duplicate-1@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate without an LPA)',
    }),
    with_identity({
        'Id': actorUserDuplicate2aID,
        'Email': 'opg-use-an-lpa+duplicate-2@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different activation code, with active access code)',
    }),
    with_identity({
        'Id': actorUserDuplicate2bID,
        'Email': 'opg-use-an-lpa+duplicate-2@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different activation code, without active access code)',
    }),
    with_identity({
        'Id': actorUserDuplicate3aID,
        'Email': 'opg-use-an-lpa+duplicate-3@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different actor, with active access code)',
    }),
    with_identity({
        'Id': actorUserDuplicate3bID,
        'Email': 'opg-use-an-lpa+duplicate-3@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different actor, without active access code)',
    }),
    with_identity({
        'Id': actorUserDuplicate4aID,
        'Email': 'opg-use-an-lpa+duplicate-4@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different activation code, with access codes)',
    }),
    with_identity({
        'Id': actorUserDuplicate4bID,
        'Email': 'opg-use-an-lpa+duplicate-4@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different activation code, with access codes)',
    }),
    with_identity({
        'Id': actorUserDuplicate5aID,
        'Email': 'opg-use-an-lpa+duplicate-5a@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different email, with LPA)',
    }),
    with_identity({
        'Id': actorUserDuplicate5bID,
        'Email': 'opg-use-an-lpa+duplicate-5b@digital.justice.gov.uk',
        'LastLogin': datetime.datetime.now().isoformat(),
        'Comment': 'Seeded data: Default test user (duplicate different email, without LPA)',
    }),
]

for i in actorUsers:
    try:
        actorUsersTable.put_item(Item=i)
        actorUsersTable.put_item(Item={'Id': 'EMAIL#' + i['Email']})
        if 'Identity' in i:
            actorUsersTable.put_item(Item={'Id': 'IDENTITY#' + i['Identity']})

        response = actorUsersTable.get_item(Key={'Id': i['Id']})
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
        'Id': '906f3720-5b43-49ce-ac66-c670860bf4ee',
        'SiriusUid': '700000000138',
        'ActorId': 23,
        'Added': '2020-08-19T15:22:32.838097Z ',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'ActivationCode': 'XW34H3HYFDDM',
        'Comment': 'Seeded data'
    },
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
        'DueBy': twoWeeks.isoformat(),
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
    },
    {
        'Id': 'ea3f1c45-b15d-4927-a83e-2e2687bce5bd',
        'SiriusUid': '700000136361',
        'ActorId': 700000136908,
        'Added': '2021-04-23T11:44:11.324804Z',
        'ActivatedOn': '2021-04-24T11:44:11.324804Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Comment': 'Seeded data'
    },
    {
        'Id': '4dc93ef3-b76f-4bef-ad0b-89852a21b823',
        'SiriusUid': '700000137237',
        'ActorId': 700000137112,
        'Added': '2021-04-23T11:44:11.324804Z',
        'ActivatedOn': '2021-04-24T11:44:11.324804Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Comment': 'Seeded data'
    },
    {
        'Id': '1c72b660-0da0-4acf-9c27-2de57c1a255b',
        'LpaUid': 'M-7890-0400-4000',
        'ActorId': '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
        'Added': '2024-09-18T12:23:45.742378Z',
        'ActivatedOn': '2024-09-18T12:23:45.742378Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Comment': 'Seeded data'
    },
    {
        'Id': '420aae9c-0301-453c-948c-0053616016e4',
        'LpaUid': 'M-0407-6149-4861',
        'ActorId': 'dde2e65b-7c8d-4f8f-a504-538c7f1bc6d6',
        'Added': '2026-01-30T23:00:00Z',
        'ActivatedOn': '2026-01-30T23:00:00Z',
        'UserId': 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
        'Comment': 'Seeded data'
    },
    {
        'Id': 'a7e9fa48-248c-41c8-a90b-aacc591b435c',
        'LpaUid': 'M-0407-6149-4861',
        'ActorId': 'dde2e65b-7c8d-4f8f-a504-538c7f1bc6d6',
        'Added': '2026-01-30T23:00:00Z',
        'ActivatedOn': '2026-01-30T23:00:00Z',
        'UserId': actorUserDuplicate1aID,
        'Comment': 'Seeded data'
    },
    {
        'Id': userLpaActorDuplicate2aID,
        'SiriusUid': '700000000047',
        'ActorId': 9,
        'Added': '2021-04-22T15:01:11.548361Z',
        'UserId': actorUserDuplicate2aID,
        'ActivationCode': 'WWFCCH41R456',
        'Comment': 'Seeded data'
    },
    {
        'Id': '052f9adf-5c63-4fc9-a29f-8d659a01192e',
        'SiriusUid': '700000000047',
        'ActorId': 9,
        'Added': '2021-04-22T15:01:11.548361Z',
        'UserId': actorUserDuplicate2bID,
        'ActivationCode': 'WWFCCH41R789',
        'Comment': 'Seeded data'
    },
    {
        'Id': userLpaActorDuplicate3aID,
        'LpaUid': '700000000138',
        'ActorId': 23,
        'Added': '2026-01-30T23:00:00Z',
        'ActivatedOn': '2026-01-30T23:00:00Z',
        'UserId': actorUserDuplicate3aID,
        'Comment': 'Seeded data'
    },
    {
        'Id': '24ab679f-05f4-f0c4-eeab-d5998f2a08ec',
        'LpaUid': '700000000138',
        'ActorId': 59,
        'Added': '2026-01-30T23:00:00Z',
        'ActivatedOn': '2026-01-30T23:00:00Z',
        'UserId': actorUserDuplicate3bID,
        'Comment': 'Seeded data'
    },
    {
        'Id': userLpaActorDuplicate4aID,
        'SiriusUid': '700000000047',
        'ActorId': 9,
        'Added': '2021-04-22T15:01:11.548361Z',
        'UserId': actorUserDuplicate4aID,
        'ActivationCode': 'WWFCCH41R123',
        'Comment': 'Seeded data'
    },
    {
        'Id': userLpaActorDuplicate4bID,
        'SiriusUid': '700000000047',
        'ActorId': 9,
        'Added': '2021-04-22T15:01:11.548361Z',
        'UserId': actorUserDuplicate4bID,
        'ActivationCode': 'WWFCCH41R456',
        'Comment': 'Seeded data'
    },
    {
        'Id': '00534eb9-85dc-c9e4-8c33-acbf2651e7dc',
        'SiriusUid': '700000000047',
        'ActorId': 9,
        'Added': '2021-04-22T15:01:11.548361Z',
        'UserId': actorUserDuplicate5aID,
        'ActivationCode': 'WWFCCH41R456',
        'Comment': 'Seeded data'
    },
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
        'auth_onelogin_account_created_event': 40,
        'account_deleted_event': 30,
        'account_activated_event': 20,
        'viewer_codes_created': 10,
        'viewer_codes_viewed' : 5,
        'added_lpa_type_hw_event' :29,
        'added_lpa_type_pfa_event' : 30,
        'download_summary_event' : 20,
        'older_lpa_needs_cleansing_event' : 34,
        'older_lpa_force_activation_key_event' : 35,
        'older_lpa_success_event' : 36,
        'oolpa_key_requested_for_donor_event' : 12,
        'oolpa_key_requested_for_attorney_event' : 13,
        'view_lpa_share_code_expired_event' : 35,
        'user_abroad_address_request_success_event' : 27,
        'full_match_key_request_success_lpa_type_hw_event' : 45,
        'full_match_key_request_success_lpa_type_pfa_event' : 37,
        'view_lpa_share_code_cancelled_event' : 28


    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=4)).strftime('%Y-%m'),
        'lpas_added': 5,
        'lpa_removed_event': 6,
        'account_created_event': 3,
        'auth_onelogin_account_created_event': 4,
        'account_deleted_event': 2,
        'account_activated_event': 2,
        'viewer_codes_created': 2,
        'viewer_codes_viewed' : 1,
        'added_lpa_type_hw_event' : 5,
        'added_lpa_type_pfa_event' : 7,
        'download_summary_event' : 4,
        'older_lpa_needs_cleansing_event' : 6,
        'older_lpa_force_activation_key_event' : 7,
        'older_lpa_success_event' : 8,
        'oolpa_key_requested_for_donor_event' : 12,
        'oolpa_key_requested_for_attorney_event' : 13,
        'view_lpa_share_code_expired_event' : 6,
        'user_abroad_address_request_success_event' : 6,
        'full_match_key_request_success_lpa_type_hw_event' : 4,
        'full_match_key_request_success_lpa_type_pfa_event' : 6,
        'view_lpa_share_code_cancelled_event' : 5
    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=3)).strftime('%Y-%m'),
        'lpas_added': 3,
        'lpa_removed_event': 7,
        'account_created_event': 5,
        'auth_onelogin_account_created_event': 6,
        'account_deleted_event': 1,
        'account_activated_event': 15,
        'viewer_codes_created': 4,
        'viewer_codes_viewed' : 3,
        'added_lpa_type_hw_event' : 7,
        'added_lpa_type_pfa_event' : 6,
        'download_summary_event' : 7,
        'older_lpa_needs_cleansing_event' : 12,
        'older_lpa_force_activation_key_event' : 14,
        'older_lpa_success_event' : 16,
        'oolpa_key_requested_for_donor_event' : 22,
        'oolpa_key_requested_for_attorney_event' : 23,
        'view_lpa_share_code_expired_event' : 6,
        'user_abroad_address_request_success_event' : 5,
        'full_match_key_request_success_lpa_type_hw_event' : 4,
        'full_match_key_request_success_lpa_type_pfa_event' : 6,
        'view_lpa_share_code_cancelled_event' : 5
    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=2)).strftime('%Y-%m'),
        'lpas_added': 7,
        'lpa_removed_event': 8,
        'account_created_event': 12,
        'auth_onelogin_account_created_event': 13,
        'account_deleted_event': 3,
        'account_activated_event': 12,
        'viewer_codes_created': 2,
        'viewer_codes_viewed' : 0,
        'added_lpa_type_hw_event' : 8,
        'added_lpa_type_pfa_event' : 6,
        'download_summary_event' : 7,
        'older_lpa_needs_cleansing_event' : 6,
        'older_lpa_force_activation_key_event' : 7,
        'older_lpa_success_event' : 8,
        'oolpa_key_requested_for_donor_event' : 32,
        'oolpa_key_requested_for_attorney_event' : 33,
        'view_lpa_share_code_expired_event' : 6,
        'user_abroad_address_request_success_event' : 5,
        'full_match_key_request_success_lpa_type_hw_event' : 4,
        'full_match_key_request_success_lpa_type_pfa_event' : 6,
        'view_lpa_share_code_cancelled_event' : 5

    },
    {
        'TimePeriod': (datetime.date.today() - relativedelta(months=1)).strftime('%Y-%m'),
        'lpas_added': 5,
        'lpa_removed_event': 9,
        'account_created_event': 15,
        'auth_onelogin_account_created_event': 16,
        'account_deleted_event': 1,
        'account_activated_event': 9,
        'viewer_codes_created': 1,
        'viewer_codes_viewed' : 0,
        'added_lpa_type_hw_event' : 8,
        'added_lpa_type_pfa_event' : 8,
        'download_summary_event' : 7,
        'older_lpa_needs_cleansing_event' : 6,
        'older_lpa_force_activation_key_event' : 7,
        'older_lpa_success_event' : 8,
        'oolpa_key_requested_for_donor_event' : 42,
        'oolpa_key_requested_for_attorney_event' : 43,
        'view_lpa_share_code_expired_event' : 6,
        'user_abroad_address_request_success_event' : 6,
        'full_match_key_request_success_lpa_type_hw_event' : 4,
        'full_match_key_request_success_lpa_type_pfa_event' : 6,
        'view_lpa_share_code_cancelled_event' : 5


    },
    {
        'TimePeriod': datetime.date.today().strftime('%Y-%m'),
        'lpas_added': 15,
        'lpa_removed_event': 10,
        'account_created_event': 6,
        'auth_onelogin_account_created_event': 7,
        'account_deleted_event': 4,
        'account_activated_event': 12,
        'viewer_codes_created': 1,
        'viewer_codes_viewed' : 1,
        'added_lpa_type_hw_event' : 8,
        'added_lpa_type_pfa_event' : 8,
        'download_summary_event' : 7,
        'older_lpa_needs_cleansing_event' : 6,
        'older_lpa_force_activation_key_event' : 7,
        'older_lpa_success_event' : 8,
        'oolpa_key_requested_for_donor_event' : 12,
        'oolpa_key_requested_for_attorney_event' : 13,
        'view_lpa_share_code_expired_event' : 6,
        'user_abroad_address_request_success_event' : 6,
        'full_match_key_request_success_lpa_type_hw_event' : 4,
        'full_match_key_request_success_lpa_type_pfa_event' : 6,
        'view_lpa_share_code_cancelled_event' : 5
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
