import argparse
import os
import boto3
import json
import datetime

# if 'AWS_ENDPOINT_DYNAMODB' in os.environ:
#     # For local development
#     dynamodb_endpoint_url = 'http://' + os.environ['AWS_ENDPOINT_DYNAMODB']
#     dynamodb = boto3.resource(
#         'dynamodb', region_name='eu-west-1', endpoint_url=dynamodb_endpoint_url)

# else:

#     if os.getenv('CI'):
#         role_arn = 'arn:aws:iam::{}:role/opg-use-an-lpa-ci'.format(
#             os.environ['AWS_ACCOUNT_ID'])
#     else:
#         role_arn = 'arn:aws:iam::{}:role/operator'.format(
#             os.environ['AWS_ACCOUNT_ID'])

#     # Get a auth token
#     session = boto3.client(
#         'sts',
#         region_name='eu-west-1',
#     ).assume_role(
#         RoleArn=role_arn,
#         RoleSessionName='lpa_codes_seeding',
#         DurationSeconds=900
#     )

#     # Create a authenticated client
#     dynamodb = boto3.resource(
#         'dynamodb',
#         region_name='eu-west-1',
#         aws_access_key_id=session['Credentials']['AccessKeyId'],
#         aws_secret_access_key=session['Credentials']['SecretAccessKey'],
#         aws_session_token=session['Credentials']['SessionToken']
#     )

# actorLpaCodesTable = dynamodb.Table(
#     os.environ['DYNAMODB_TABLE_ACTOR_CODES'])


class LpaCodesSeeder:
    input_json_path = ''
    aws_account_id = ''
    aws_iam_session = ''

    def __init__(self, input_json):
        self.input_json_path = input_json

    def put_actor_codes(self):
        with open(self.input_json_path) as f:
            actorLpaCodes = json.load(f)

        for actorLpaCode in actorLpaCodes:
            print(actorLpaCode)
            # actorLpaCodesTable.put_item(
            #     Item=actorLpaCode,
            # )
            # response = actorLpaCodesTable.get_item(
            #     Key={'ActorCode': actorLpaCode['ActorCode']}
            # )
            # print(response)


def main():
    parser = argparse.ArgumentParser(
        description="Put actor codes into the lpa-codes API service.")

    parser.add_argument("input_json", nargs='?', default="./seeding_data.json", type=str,
                        help="Path to config file produced by terraform")
    args = parser.parse_args()

    work = LpaCodesSeeder(args.input_json)
    work.put_actor_codes()


if __name__ == "__main__":
    main()
