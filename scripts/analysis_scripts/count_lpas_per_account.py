import argparse
import json
import csv
import boto3

class AccountLookup:
    aws_account_id = ''
    aws_iam_session = ''
    aws_dynamodb_client = ''
    environment = ''
    output_json = []

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
            role_arn = 'arn:aws:iam::{}:role/read-only-db'.format(
                self.aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{}:role/operator'.format(
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



    def count_by_user(self):
        with open('lpas_per_account.csv', 'w', newline='') as f:
            writer = csv.writer(
                f, quoting=csv.QUOTE_NONNUMERIC)
            writer.writerow(["lpas_in_account_{}".format(self.environment)])

            actor_users = self.get_actor_users()

            for user in actor_users:
                lpas = self.get_lpas_by_user_id(user['Id']['S'])
                count = len(lpas)
                writer.writerow([str(count)])


def main():
    arguments = argparse.ArgumentParser(
        description="Look up an account by email address.")
    arguments.add_argument("--environment",
                        default="demo",
                        help="The environment to target. Defaults to production")

    arguments.add_argument('--json', dest='output_json', action='store_const',
                        const=True, default=False,
                        help='Output json data instead of plaintext to terminal')

    args = arguments.parse_args()
    work = AccountLookup(args.environment)
    work.count_by_user()




if __name__ == "__main__":
    main()
