import boto3
import argparse
import csv
from boto3.dynamodb.conditions import Key
from datetime import datetime
from dateutil import parser
from datetime import date

class OrganisationsViewedChecker:
    aws_account_id = ''
    aws_iam_session = ''
    aws_dynamodb_client = ''
    environment = ''
    start_date = ''
    end_date = ''

    def __init__(self, environment, start_date, end_date):
        self.aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment
        self.aws_account_id = self.aws_account_ids.get(
            self.environment, "367815980639")

        self.start_date = date.fromisoformat(start_date)
        self.end_date = date.fromisoformat(end_date)
        assert self.start_date < self.end_date

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

    def get_viewer_codes(self):
        paginator = self.aws_dynamodb_client.get_paginator("scan")
        TableName = '{}-ViewerActivity'.format(self.environment)
        FilterExpression = "attribute_exists(ViewedBy) AND Viewed BETWEEN :fromdate AND :todate"

        for page in paginator.paginate(
                TableName=TableName,
                FilterExpression=FilterExpression,
                ExpressionAttributeValues={
                    ':fromdate': {'S': str(self.start_date)},
                    ':todate': {'S': str(self.end_date)}
                }):
            yield from page["Items"]

    def get_created_by_date(self, viewer_code):
        response = self.aws_dynamodb_client.get_item(
            TableName='{}-ViewerCodes'.format(self.environment),
            Key={
                'ViewerCode': {'S': viewer_code}
            }
        )
        date = parser.isoparse(response['Item']['Added']['S']).date()
        return date

    def write_csv(self):
        print('Collecting data and writing CSV...')
        n = 1
        with open('some.csv', 'w', newline='') as f:
            writer = csv.writer(
                f, quoting=csv.QUOTE_NONNUMERIC)
            writer.writerow(["  org_name_string", "org_viewed_date", "code_created_date"])
            viewer_codes = self.get_viewer_codes()

            for Item in viewer_codes:
                org = Item['ViewedBy']['S']
                viewed_date = parser.isoparse(Item['Viewed']['S']).date()
                created_date = self.get_created_by_date(Item['ViewerCode']['S'])
                writer.writerow([str(org), viewed_date, created_date])
                n += 1
            print("Done! Collected {} records".format(n))


def main():
    parser = argparse.ArgumentParser(
        description="Exports a CSV file of organisation names used with Viewer codes.")
    parser.add_argument("--environment",
                        default="production",
                        help="The environment to get organistion names for")

    parser.add_argument("--start_date",
                        default="2020-07-17",
                        help="Where to start collecting data from. Defaults to the launch of the service")

    parser.add_argument("--end_date",
                        default=str(date.today()),
                        help="Where to end collecting data. Defaults to today")

    args = parser.parse_args()
    work = OrganisationsViewedChecker(
        args.environment,
        args.start_date,
        args.end_date)
    work.write_csv()


if __name__ == "__main__":
    main()
