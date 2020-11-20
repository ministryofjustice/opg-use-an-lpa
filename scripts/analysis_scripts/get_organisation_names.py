import boto3
import argparse
import datetime
from dateutil.relativedelta import relativedelta
import requests
import json
import os
import csv


class AccountsCreatedChecker:
    aws_account_id = ''
    aws_iam_session = ''
    aws_dynamodb_client = ''
    environment = ''
    startdate = ''
    enddate = ''
    viewer_codes_created_monthly_totals = {}
    viewer_codes_created_total = 0
    viewer_codes_viewed_monthly_totals = {}
    viewer_codes_viewed_total = 0
    json_output = ''

    def __init__(self, environment):
        self.aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment
        self.aws_account_id = self.aws_account_ids.get(
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

    def get_viewer_codes(self):
        paginator = self.aws_dynamodb_client.get_paginator("scan")
        TableName = '{}-ViewerCodes'.format(self.environment)

        for page in paginator.paginate(TableName=TableName):
            yield from page["Items"]

    def get(self):
        with open('some.csv', 'w', newline='') as f:
            writer = csv.writer(
                f, quoting=csv.QUOTE_NONNUMERIC)
            writer.writerow(["  org_name_string"])
            viewer_codes = self.get_viewer_codes()

            for Item in viewer_codes:
                org = Item['Organisation']['S']
                writer.writerow([str(org)])


def main():
    parser = argparse.ArgumentParser(
        description="Print the total accounts created. Starts from teh first of the month of the given start date.")
    parser.add_argument("--environment",
                        default="production",
                        help="The environment to provide stats for")

    args = parser.parse_args()
    work = AccountsCreatedChecker(
        args.environment)
    work.get()


if __name__ == "__main__":
    main()
