import argparse
import csv
from datetime import date
from dateutil import parser
import boto3

class AccountLookup:
    aws_account_id = ''
    aws_iam_session = ''
    aws_dynamodb_client = ''
    environment = ''

    def __init__(self, environment):
        aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment

        self.aws_account_id = aws_account_ids.get(
            self.environment, "367815980639")

        self.set_iam_role_session()

        self.aws_dynamodb_client = boto3.client(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def set_iam_role_session(self):
        role_arn = 'arn:aws:iam::{}:role/read-only-db'.format(
            self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='getting_service_statistics',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_actor_users(self):
        paginator = self.aws_dynamodb_client.get_paginator("scan")
        for page in paginator.paginate(
                TableName='{}-ActorUsers'.format(self.environment),
                FilterExpression="attribute_exists(Email)",
                ):
            yield from page["Items"]

    def get_lpas(self):
        paginator = self.aws_dynamodb_client.get_paginator("scan")
        for page in paginator.paginate(
                TableName='{}-UserLpaActorMap'.format(self.environment),
                FilterExpression="attribute_exists(SiriusUid)",
                ):
            yield from page["Items"]

    def get_lpas_by_user_id(self, user_id):
        response = self.aws_dynamodb_client.query(
            IndexName='UserIndex',
            TableName='{}-UserLpaActorMap'.format(self.environment),
            KeyConditionExpression='UserId = :user_id',
            ExpressionAttributeValues={
                ':user_id': {'S': user_id}
            }
        )
        lpas = {}
        for lpa in response['Items']:
            lpas.update({lpa['SiriusUid']['S']:lpa['Added']['S']})
        return lpas

    def get_users_by_id(self, user_id):
        response = self.aws_dynamodb_client.query(
            TableName='{}-ActorUsers'.format(self.environment),
            KeyConditionExpression='Id = :user_id',
            ExpressionAttributeValues={
                ':user_id': {'S': user_id}
            }
        )
        if response['Items']:
            return response['Items'][0]

    def get_structured_account_data(self, user):
        email = user['Email']['S']
        last_login = 'Never logged in'
        if 'LastLogin' in user:
            last_login = user['LastLogin']['S']
        activation_status = 'Activated'
        if 'ActivationToken' in user:
            activation_status = 'Pending Activation'

        print(str(email),"\nActivation Status: {}".format(activation_status), "\nLast Login: {}".format(last_login))

    def get_by_lpa(self, lpa_id):
        print('Collecting data...')
        count = 0
        lpas = self.get_lpas()

        for item in lpas:
            if item['SiriusUid']['S'] in lpa_id:
                user = self.get_users_by_id(item['UserId']['S'])
                if user:
                  self.get_structured_account_data(user)
                  lpas = self.get_lpas_by_user_id(item['UserId']['S'])
                  print(lpas)
                  count += 1
        print("Done! Record Count: {}".format(count))

    def get_by_email(self,email_address):
        print('Collecting data...')
        count = 0
        actor_users = self.get_actor_users()

        for item in actor_users:
            if item['Email']['S'] in email_address:
                self.get_structured_account_data(item)
                lpas = self.get_lpas_by_user_id(item['Id']['S'])
                print(lpas)
                count += 1
        print("Done! Record Count: {}".format(count))


def main():
    arguments = argparse.ArgumentParser(
        description="Look up an account by email address.")
    arguments.add_argument("--environment",
                        default="production",
                        help="The environment to target")

    arguments.add_argument("--email_address",
                        default="",
                        help="Email address to look up")

    arguments.add_argument("--lpa_id",
                        default="",
                        help="Sirius LPA ID to look up")

    arguments.add_argument('--csv', dest='make_csv_file', action='store_const',
                        const=True, default=False,
                        help='Write a csv file instead of printing to terminal')

    args = arguments.parse_args()
    work = AccountLookup(args.environment)

    if args.email_address:
        work.get_by_email(args.email_address.lower())
    if args.lpa_id:
        print(work.get_by_lpa(args.lpa_id))


if __name__ == "__main__":
    main()
