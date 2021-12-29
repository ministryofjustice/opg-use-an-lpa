import argparse
import json
import os
from datetime import date
from datetime import datetime
import sys
import botocore
import boto3
import requests


class ECRScanChecker:
    aws_account_id = ''
    images_to_check = []
    report = ''

    def __init__(self, search_term):
        self.aws_account_id = 311462405659  # management account id
        aws_iam_session = self.set_iam_role_session()

        self.aws_ecr_client = self.get_aws_client(
            'ecr',
            aws_iam_session)

        self.aws_inspector2_client = self.get_aws_client(
            'inspector2',
            aws_iam_session)

        self.images_to_check = self.get_repositories(search_term)

    @staticmethod
    def get_aws_client(client_type, aws_iam_session, region="eu-west-1"):
        client = boto3.client(
            client_type,
            region_name=region,
            aws_access_key_id=aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=aws_iam_session['Credentials']['SessionToken'])
        return client

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/opg-use-an-lpa-ci'
        else:
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/operator'

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='checking_ecr_image_scan',
            DurationSeconds=900
        )
        return session

    def get_repositories(self, search_term):
        images_to_check = []
        response = self.aws_ecr_client.describe_repositories()
        for repository in response['repositories']:
            if search_term in repository['repositoryName']:
                images_to_check.append(repository['repositoryName'])
        return images_to_check

    def recursive_wait(self, tag):
        print('Waiting for ECR scans to complete...')
        for image in self.images_to_check:
            self.wait_for_scan_completion(image, tag)
        print('ECR image scans complete')

    def wait_for_scan_completion(self, image, tag):
        try:
            waiter = self.aws_ecr_client.get_waiter('image_scan_complete')
            waiter.wait(
                repositoryName=image,
                imageId={
                    'imageTag': tag
                },
                WaiterConfig={
                    'Delay': 5,
                    'MaxAttempts': 1
                }
            )
        except botocore.exceptions.WaiterError as error:
            if (
                'Error' in error.last_response
                and not 'ScanNotFoundException' in error.last_response['Error']['Code']
            ):
                print(error.last_response['Error']['Code'],
                      error.last_response['Error']['Message'])
                sys.exit(1)
            if (
                'Error' in error.last_response
                and 'ScanNotFoundException' in error.last_response['Error']['Code']
            ):
                print(
                    f'No ECR image scan results for image {image}, tag {tag}')

    def recursive_check_make_report(self, tag, date_inclusive, report_limit, print_to_terminal):
        print('Checking ECR scan results...')
        for image in self.images_to_check:
            try:
                findings = self.list_findings(
                    image, tag, date_inclusive, report_limit)
                if findings['findings'] != []:

                    self.report = (
                        f'\n\n:warning: *AWS ECR Scan found results for {image}:* \n'
                        f'Vulnerability Reports Found.\n'
                        f'Displaying the first {report_limit} in order of severity\n\n'
                    )

                    for finding in findings['findings']:
                        cve = finding['title']
                        severity = finding['severity']
                        description = 'None'
                        if 'description' in finding:
                            description = finding['description']
                        link = finding['packageVulnerabilityDetails']['sourceUrl']
                        vuln_type = finding['type']
                        result = (
                            f'*Image:* {image} \n'
                            f'*Tag: * {tag} \n'
                            f'*Severity:* {severity} \n'
                            f'*Type:* `{vuln_type}`\n'
                            f'*CVE:* {cve} \n'
                            f'*Description:* {description} \n'
                            f'*Link:* `{link}`\n\n'
                        )
                        self.report += result
                    if print_to_terminal:
                        print(self.report)
            except botocore.exceptions.ClientError as error:
                print(error.response['Error']['Code'],
                      error.response['Error']['Message'])
                sys.exit(1)

    def list_findings(self, image, tag, date_inclusive, report_limit):
        date_start_inclusive = datetime.combine(
            date_inclusive, datetime.min.time())

        date_end_inclusive = datetime.combine(
            date_inclusive, datetime.max.time())
        response = self.aws_inspector2_client.list_findings(
            filterCriteria={
                'awsAccountId': [
                    {
                        'comparison': 'EQUALS',
                        'value': str(self.aws_account_id)
                    },
                ],
                'ecrImagePushedAt': [
                    {
                        'endInclusive': date_end_inclusive,
                        'startInclusive': date_start_inclusive
                    },
                ],
                'ecrImageRepositoryName': [
                    {
                        'comparison': 'EQUALS',
                        'value': image
                    },
                ],
                'ecrImageTags': [
                    {
                        'comparison': 'EQUALS',
                        'value': tag
                    },
                ],
            },
            maxResults=report_limit,
            sortCriteria={
                'field': 'SEVERITY',
                'sortOrder': 'DESC'
            }
        )
        return response

    def post_to_slack(self, slack_webhook):
        if self.report != '':
            build_url = os.getenv('CIRCLE_BUILD_URL', '')
            circleci_branch = os.getenv('CIRCLE_BRANCH', '')
            branch_info = (
                f'*Github Branch:* {circleci_branch}\n'
                f'*CircleCI Job Link:* {build_url}\n\n'
            )
            self.report += branch_info

            post_data = json.dumps({'text': self.report})
            response = requests.post(
                slack_webhook, data=post_data,
                headers={'Content-Type': 'application/json'}
            )
            if response.status_code != 200:
                raise ValueError(
                    f'Request to slack returned an error {response.status_code}, the response is:\n'
                    f'{response.text}'
                )


def main():
    parser = argparse.ArgumentParser(
        description='Check ECR Scan results for all service container images.')
    parser.add_argument('--search',
                        default='',
                        help='The root part oof the ECR repositry path, for example online-lpa')
    parser.add_argument('--tag',
                        default='latest',
                        help='Image tag to check scan results for.')
    parser.add_argument('--ecr_pushed_date_inclusive',
                        default=date.today(),
                        help='ECR Image push datetime in format YYYY-MM-dd')
    parser.add_argument('--result_limit',
                        default=5,
                        help='How many results for each image to return. Defaults to 5')
    parser.add_argument('--slack_webhook',
                        default=os.getenv('SLACK_WEBHOOK'),
                        help='Webhook to use, determines what channel to post to')
    parser.add_argument('--print_to_terminal', dest='print_to_terminal', action='store_const',
                        const=True, default=False,
                        help='print findings to terminal')
    parser.add_argument('--skip_post_to_slack', dest='skip_post_to_slack', action='store_const',
                        const=False, default=True,
                        help='Optionally turn off posting messages to slack')

    args = parser.parse_args()
    work = ECRScanChecker(args.search)
    work.recursive_check_make_report(
        args.tag,
        args.ecr_pushed_date_inclusive,
        args.result_limit,
        args.print_to_terminal
    )
    if args.skip_post_to_slack and args.slack_webhook is not None:
        work.post_to_slack(args.slack_webhook)
    else:
        print('Skipping post of results to slack')


if __name__ == '__main__':
    main()
