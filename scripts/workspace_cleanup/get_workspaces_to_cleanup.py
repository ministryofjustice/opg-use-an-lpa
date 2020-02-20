import calendar
import datetime
import time
import urllib.request
import boto3
import argparse
import json
import os


class WorkspaceWatcher:
    aws_account_id = "367815980639"
    aws_iam_session = ''
    aws_dynamodb_client = ''
    workspace_list = ""
    dynamodb_scan_response = ""

    def __init__(self):
        self.set_iam_role_session()
        self.aws_dynamodb_client = boto3.client(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/opg-use-an-lpa-ci'.format(
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
            RoleSessionName='workspace_cleanup',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_workspaces_from_table(self):
        self.dynamodb_scan_response = self.aws_dynamodb_client.scan(
            TableName='WorkspaceCleanup',
            AttributesToGet=[
                'WorkspaceName',
            ],
            Limit=123
        )

    def return_workspaces(self):
        for item in self.dynamodb_scan_response["Items"]:
            self.workspace_list = self.workspace_list + \
                " " + item["WorkspaceName"]["S"]
        print(self.workspace_list)


def main():
    work = WorkspaceWatcher()
    work.get_workspaces_from_table()
    work.return_workspaces()


if __name__ == "__main__":
    main()
