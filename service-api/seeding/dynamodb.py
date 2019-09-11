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
        'SiriusUid': "700000001094",
        'Expires': "2020-01-01 12:34:56",
    },
    {
        'ViewerCode': "987654321098",
        'SiriusUid': "700000000138",
        'Expires': "2020-01-01 12:34:56",
    },
    {
        'ViewerCode': "222222222222",
        'SiriusUid': "222222222222",
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
        'ActorLpaCode': "100000000070",
        'ActorSiriusUid': "700000000070",
        'SiriusUid': "700000000047",
    },
    {
        'ActorLpaCode': "100000000096",
        'ActorSiriusUid': "700000000096",
        'SiriusUid': "700000000047",
    },
    {
        'ActorLpaCode': "100000000161",
        'ActorSiriusUid': "700000000161",
        'SiriusUid': "700000000138",
    },
    {
        'ActorLpaCode': "100000000187",
        'ActorSiriusUid': "700000000187",
        'SiriusUid': "700000000138",
    },
    {
        'ActorLpaCode': "100000000286",
        'ActorSiriusUid': "700000000286",
        'SiriusUid': "700000000252",
    },
    {
        'ActorLpaCode': "100000000302",
        'ActorSiriusUid': "700000000302",
        'SiriusUid': "700000000252",
    },
    {
        'ActorLpaCode': "100000000377",
        'ActorSiriusUid': "700000000377",
        'SiriusUid': "700000000344",
    },
    {
        'ActorLpaCode': "100000000427",
        'ActorSiriusUid': "700000000427",
        'SiriusUid': "700000000344",
    },
    {
        'ActorLpaCode': "100000000468",
        'ActorSiriusUid': "700000000468",
        'SiriusUid': "700000000435",
    },
    {
        'ActorLpaCode': "100000000484",
        'ActorSiriusUid': "700000000484",
        'SiriusUid': "700000000435",
    },
    {
        'ActorLpaCode': "100000000559",
        'ActorSiriusUid': "700000000559",
        'SiriusUid': "700000000526",
    },
    {
        'ActorLpaCode': "100000000609",
        'ActorSiriusUid': "700000000609",
        'SiriusUid': "700000000526",
    },
    {
        'ActorLpaCode': "100000000641",
        'ActorSiriusUid': "700000000641",
        'SiriusUid': "700000000617",
    },
    {
        'ActorLpaCode': "100000000690",
        'ActorSiriusUid': "700000000690",
        'SiriusUid': "700000000617",
    },
    {
        'ActorLpaCode': "100000000732",
        'ActorSiriusUid': "700000000732",
        'SiriusUid': "700000000708",
    },
    {
        'ActorLpaCode': "100000000781",
        'ActorSiriusUid': "700000000781",
        'SiriusUid': "700000000708",
    },
    {
        'ActorLpaCode': "100000000823",
        'ActorSiriusUid': "700000000823",
        'SiriusUid': "700000000799",
    },
    {
        'ActorLpaCode': "100000000880",
        'ActorSiriusUid': "700000000880",
        'SiriusUid': "700000000799",
    },
    {
        'ActorLpaCode': "100000000948",
        'ActorSiriusUid': "700000000948",
        'SiriusUid': "700000000914",
    },
    {
        'ActorLpaCode': "100000000997",
        'ActorSiriusUid': "700000000997",
        'SiriusUid': "700000000914",
    },
    {
        'ActorLpaCode': "100000001037",
        'ActorSiriusUid': "700000001037",
        'SiriusUid': "700000001003",
    },
    {
        'ActorLpaCode': "100000001052",
        'ActorSiriusUid': "700000001052",
        'SiriusUid': "700000001003",
    },
    {
        'ActorLpaCode': "100000001128",
        'ActorSiriusUid': "700000001128",
        'SiriusUid': "700000001094",
    },
    {
        'ActorLpaCode': "100000001144",
        'ActorSiriusUid': "700000001144",
        'SiriusUid': "700000001094",
    },
    {
        'ActorLpaCode': "100000001177",
        'ActorSiriusUid': "700000001177",
        'SiriusUid': "700000001094",
    },
    {
        'ActorLpaCode': "100000001219",
        'ActorSiriusUid': "700000001219",
        'SiriusUid': "700000001185",
    },
    {
        'ActorLpaCode': "100000001268",
        'ActorSiriusUid': "700000001268",
        'SiriusUid': "700000001185",
    },
    {
        'ActorLpaCode': "100000001300",
        'ActorSiriusUid': "700000001300",
        'SiriusUid': "700000001276",
    },
    {
        'ActorLpaCode': "100000001326",
        'ActorSiriusUid': "700000001326",
        'SiriusUid': "700000001276",
    },
    {
        'ActorLpaCode': "100000001391",
        'ActorSiriusUid': "700000001391",
        'SiriusUid': "700000001367",
    },
    {
        'ActorLpaCode': "100000001417",
        'ActorSiriusUid': "700000001417",
        'SiriusUid': "700000001367",
    },
    {
        'ActorLpaCode': "100000001482",
        'ActorSiriusUid': "700000001482",
        'SiriusUid': "700000001458",
    },
    {
        'ActorLpaCode': "100000001508",
        'ActorSiriusUid': "700000001508",
        'SiriusUid': "700000001458",
    },
    {
        'ActorLpaCode': "100000001573",
        'ActorSiriusUid': "700000001573",
        'SiriusUid': "700000001540",
    },
    {
        'ActorLpaCode': "100000001599",
        'ActorSiriusUid': "700000001599",
        'SiriusUid': "700000001540",
    },
    {
        'ActorLpaCode': "100000001664",
        'ActorSiriusUid': "700000001664",
        'SiriusUid': "700000001631",
    },
    {
        'ActorLpaCode': "100000001680",
        'ActorSiriusUid': "700000001680",
        'SiriusUid': "700000001631",
    },
    {
        'ActorLpaCode': "100000001755",
        'ActorSiriusUid': "700000001755",
        'SiriusUid': "700000001722",
    },
    {
        'ActorLpaCode': "100000001805",
        'ActorSiriusUid': "700000001805",
        'SiriusUid': "700000001722",
    },
    {
        'ActorLpaCode': "100000001847",
        'ActorSiriusUid': "700000001847",
        'SiriusUid': "700000001813",
    },
    {
        'ActorLpaCode': "100000001896",
        'ActorSiriusUid': "700000001896",
        'SiriusUid': "700000001813",
    },
    {
        'ActorLpaCode': "100000001938",
        'ActorSiriusUid': "700000001938",
        'SiriusUid': "700000001904",
    },
    {
        'ActorLpaCode': "100000001953",
        'ActorSiriusUid': "700000001953",
        'SiriusUid': "700000001904",
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
