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
    expires_delta_days = 1

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

    def add_workspace(self, workspace):
        workspace = workspace
        expires = calendar.timegm((datetime.timedelta(
            days=self.expires_delta_days)+datetime.datetime.today()).timetuple())
        try:
            print('Marking workspace for cleanup...')
            response = self.aws_dynamodb_client.put_item(
                TableName='WorkspaceCleanup',
                Item={
                    'WorkspaceName': {'S': workspace},
                    'ExpiresTTL': {'N': str(expires)}
                }
            )
            print("Workspace marked for cleanup")

        except:
            print("Error writing to dynamodb")


def main():
    parser = argparse.ArgumentParser(
        description="Mark workspace for clean up.")

    parser.add_argument("workspace", type=str,
                        help="Name of terraform workspace to mark for clean up")

    args = parser.parse_args()

    work = WorkspaceWatcher()
    work.add_workspace(args.workspace)


if __name__ == "__main__":
    main()
