import argparse
import logging
import json
import boto3
logging.basicConfig(encoding='utf-8', level=logging.INFO)


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
        if self.environment == "production":
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/read-only-db'
        else:
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/operator'

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

    def get_actor_users_by_index(self, email):
        response = self.aws_dynamodb_client.query(
            IndexName='EmailIndex',
            TableName=f'{self.environment}-ActorUsers',
            KeyConditionExpression='Email = :email',
            ExpressionAttributeValues={
                ':email': {'S': email}
            },
            ProjectionExpression='Id,Email,LastLogin,ActivationToken'
        )
        logging.info(response)
        return response

    def get_lpas(self):
        paginator = self.aws_dynamodb_client.get_paginator("scan")
        for page in paginator.paginate(
                TableName=f'{self.environment}-UserLpaActorMap',
                FilterExpression="attribute_exists(SiriusUid)",
        ):
            yield from page["Items"]

    def get_lpas_by_user_id(self, user_id):
        logging.info('getting LPAs for user id: %s', user_id)
        response = self.aws_dynamodb_client.query(
            IndexName='UserIndex',
            TableName=f'{self.environment}-UserLpaActorMap',
            KeyConditionExpression='UserId = :user_id',
            ExpressionAttributeValues={
                ':user_id': {'S': user_id}
            },
            ProjectionExpression='SiriusUid,Added,ActorId,DueBy'
        )
        lpas = {}
        for lpa in response['Items']:
            lpas.update(lpa)
        return lpas

    def get_users_by_id(self, user_id):
        response = self.aws_dynamodb_client.query(
            TableName=f'{self.environment}-ActorUsers',
            KeyConditionExpression='Id = :user_id',
            ExpressionAttributeValues={
                ':user_id': {'S': user_id}
            }
        )
        if response['Items']:
            return response['Items'][0]
        return None

    @classmethod
    def get_structured_account_data(cls, user, lpas):
        email = user['Email']['S']
        last_login = 'Never logged in'
        if 'LastLogin' in user:
            last_login = user['LastLogin']['S']
        activation_status = 'Activated'
        if 'ActivationToken' in user:
            activation_status = 'Pending Activation'

        account_data = {
            "email": email,
            "last_login": last_login,
            "activation_status": activation_status,
            "lpas": [lpas]
        }

        return account_data

    @classmethod
    def print_plaintext(cls, input_text):
        for account in input_text:
            print(
                f"\n{str(account['email'])}",
                f"\nActivation Status: {account['activation_status']}",
                f"\nLast Login: {account['last_login']}",
                f"\nLPAs: {json.dumps(account['lpas'], indent=2)}",
                "\n"
            )

    def get_by_lpa(self, lpa_id):
        output_json = []
        logging.info('looking up account for LPA: %s', lpa_id)
        lpas = self.get_lpas()

        for item in lpas:
            if item['SiriusUid']['S'] in lpa_id:
                user = self.get_users_by_id(item['UserId']['S'])
                if user:
                    lpas = self.get_lpas_by_user_id(item['UserId']['S'])
                    account_data = self.get_structured_account_data(user, lpas)
                    output_json.append(account_data)
        return output_json

    def get_by_email(self, email_address):
        output_json = []
        logging.info('looking up account for user: %s', email_address)
        actor_users = self.get_actor_users_by_index(email_address)

        for user in actor_users['Items']:
            logging.info('User found: %s', user['Id']['S'])
            lpas = self.get_lpas_by_user_id(user['Id']['S'])
            account_data = self.get_structured_account_data(user, lpas)
            output_json.append(account_data)
        return output_json


def main():
    arguments = argparse.ArgumentParser(
        description="Look up an account by email address.")
    arguments.add_argument("--environment",
                           default="production",
                           help="The environment to target. Defaults to production")

    arguments.add_argument("--email_address",
                           default="",
                           help="Email address to look up")

    arguments.add_argument("--lpa_id",
                           default="",
                           help="Sirius LPA ID to look up")

    arguments.add_argument('--json', dest='output_json', action='store_const',
                           const=True, default=False,
                           help='Output json data instead of plaintext to terminal')

    args = arguments.parse_args()
    work = AccountLookup(args.environment)

    if args.email_address:
        response = work.get_by_email(args.email_address.lower())
    if args.lpa_id:
        response = work.get_by_lpa(args.lpa_id)

    if args.output_json:
        print(json.dumps(response))
    else:
        work.print_plaintext(response)


if __name__ == "__main__":
    main()
