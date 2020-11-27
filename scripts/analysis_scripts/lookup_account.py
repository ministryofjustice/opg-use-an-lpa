import boto3
import argparse
import csv
from boto3.dynamodb.conditions import Key
from datetime import datetime
from dateutil import parser
from datetime import date

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
        TableName = '{}-ActorUsers'.format(self.environment)
        FilterExpression = "attribute_exists(Email)"

        for page in paginator.paginate(
                TableName=TableName,
                FilterExpression=FilterExpression,
                ):
            yield from page["Items"]

    def get_lpas(self, id):
        TableName='{}-UserLpaActorMap'.format(self.environment)
        IndexName='UserIndex',
        KeyConditionExpression='UserId = :user_id'
        ExpressionAttributeValues={
            ':user_id': {'S': id}
        }
        response = self.aws_dynamodb_client.query(
            IndexName='UserIndex',
            TableName=TableName,
            KeyConditionExpression=KeyConditionExpression,
            ExpressionAttributeValues=ExpressionAttributeValues
        )
        lpas = {}
        for lpa in response['Items']:
            lpas.update({lpa['SiriusUid']['S']:str(parser.isoparse(lpa['Added']['S']).date())})
        return lpas

    def write_csv(self,email_address):
        print('Collecting data and writing CSV...')
        n = 0
        csv_filename = "account-lookup-{}.csv".format((date.today()))
        with open(csv_filename, 'w', newline='') as f:
            writer = csv.writer(
                f, quoting=csv.QUOTE_NONNUMERIC)
            writer.writerow(["email", "last_login_datetime","lpas_added"])
            viewer_codes = self.get_actor_users()

            for Item in viewer_codes:
                if Item['Email']['S'] in email_address:
                    email = Item['Email']['S']
                    last_login = 'Never logged in'
                    if 'LastLogin' in Item:
                        last_login = Item['LastLogin']['S']
                    lpas = self.get_lpas(Item['Id']['S'])
                    writer.writerow([str(email), "Last Login: {}".format(last_login), lpas])
                    n += 1
            print("Done! Collected {} records".format(n))

    def print_results(self,email_address):
        print('Collecting data...')
        n = 0

        viewer_codes = self.get_actor_users()

        for Item in viewer_codes:
            if Item['Email']['S'] in email_address:
                email = Item['Email']['S']
                last_login = Item['LastLogin']['S']
                lpas = self.get_lpas_query(Item['Id']['S'])
                print(str(email), "Last Login: {}".format(last_login))
                print(lpas)
                n += 1
        print("Done! Record Count: {}".format(n))


def main():
    parser = argparse.ArgumentParser(
        description="Look up an account by email address.")
    parser.add_argument("--environment",
                        default="production",
                        help="The environment to target")

    parser.add_argument("--email_address",
                        default="",
                        help="Email address to look up")

    parser.add_argument('--csv', dest='make_csv_file', action='store_const',
                        const=True, default=False,
                        help='Write a csv file instead of printing to terminal')

    args = parser.parse_args()
    work = AccountLookup(args.environment)

    if args.make_csv_file:
        work.write_csv(args.email_address)
    else:
        work.print_results(args.email_address)


if __name__ == "__main__":
    main()
