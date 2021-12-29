import botocore
import boto3
import argparse
import requests
import json
import os
import pprint
from datetime import date
from datetime import datetime


class ECRScanChecker:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecr_client = ''
    images_to_check = []
    tag = ''
    report = ''
    report_limit = ''
    date_start_inclusive = ''
    date_end_inclusive = ''

    def __init__(self, date_inclusive, report_limit, search_term):
        self.report_limit = int(report_limit)
        self.aws_account_id = 311462405659
        self.date_start_inclusive = datetime.combine(
            date_inclusive, datetime.min.time())  # management account id
        self.date_end_inclusive = datetime.combine(
            date_inclusive, datetime.max.time())  # management account id
        self.set_iam_role_session()
        self.aws_ecr_client = boto3.client(
            'ecr',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])
        self.aws_inspector2_client = boto3.client(
            'inspector2',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])
        self.images_to_check = self.get_repositories(search_term)

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
            RoleSessionName='checking_ecr_image_scan',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_repositories(self, search_term):
        images_to_check = []
        response = self.aws_ecr_client.describe_repositories()
        for repository in response["repositories"]:
            if search_term in repository["repositoryName"]:
                images_to_check.append(repository["repositoryName"])
        return images_to_check

    def recursive_wait(self, tag):
        print("Waiting for ECR scans to complete...")
        for image in self.images_to_check:
            self.wait_for_scan_completion(image, tag)
        print("ECR image scans complete")

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
            if 'Error' in error.last_response and 'ScanNotFoundException' in error.last_response['Error']['Code']:
                print("No ECR image scan results for image {0}, tag {1}".format(
                    image, tag))
            if 'Error' in error.last_response and not 'ScanNotFoundException' in error.last_response['Error']['Code']:
                print(error.last_response['Error']['Code'],
                      error.last_response['Error']['Message'])
                exit(1)

    def recursive_check_make_report(self, tag):
        print("Checking ECR scan results...")
        for image in self.images_to_check:
            try:
                findings = self.list_findings(image, tag)
                if findings["findings"] != []:
                    title = "\n\n:warning: *AWS ECR Scan found results for {}:* \n".format(
                        image)

                    severity_counts = "Vulnerability Reports Found.\nDisplaying the first {} in order of severity\n\n".format(
                        self.report_limit)

                    self.report = title + severity_counts

                    for finding in findings["findings"]:
                        cve = finding["title"]
                        severity = finding["severity"]

                        description = "None"
                        if "description" in finding:
                            description = finding["description"]

                        link = finding["findingArn"]
                        result = "*Image:* {0} \n*Tag:* {1} \n*Severity:* {2} \n*CVE:* {3} \n*Description:* {4} \n*Link:* `{5}`\n\n".format(
                            image, tag, severity, cve, description, link)
                        self.report += result
                    print(self.report)
            except botocore.exceptions.ClientError as error:
                print("ERROR MESSAGE!!", error.response['Error']['Code'],
                      error.response['Error']['Message'])
                exit(1)

    def list_findings(self, image, tag):
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
                        'endInclusive': self.date_end_inclusive,
                        'startInclusive': self.date_start_inclusive
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
            maxResults=self.report_limit,
            sortCriteria={
                'field': 'SEVERITY',
                'sortOrder': 'DESC'
            }
        )
        return response

    def post_to_slack(self, slack_webhook):
        if self.report != "":
            branch_info = "*Github Branch:* {0}\n*CircleCI Job Link:* {1}\n\n".format(
                os.getenv('CIRCLE_BRANCH', ""),
                os.getenv('CIRCLE_BUILD_URL', ""))
            self.report += branch_info
            print(self.report)

            post_data = json.dumps({"text": self.report})
            response = requests.post(
                slack_webhook, data=post_data,
                headers={'Content-Type': 'application/json'}
            )
            if response.status_code != 200:
                raise ValueError(
                    'Request to slack returned an error %s, the response is:\n%s'
                    % (response.status_code, response.text)
                )


def main():
    parser = argparse.ArgumentParser(
        description="Check ECR Scan results for all service container images.")
    parser.add_argument("--search",
                        default="",
                        help="The root part oof the ECR repositry path, for example online-lpa")
    parser.add_argument("--tag",
                        default="latest",
                        help="Image tag to check scan results for.")
    parser.add_argument("--ecr_pushed_date_inclusive",
                        default=date.today(),
                        help="ECR Image push datetime in format YYYY-MM-dd")
    parser.add_argument("--result_limit",
                        default=5,
                        help="How many results for each image to return. Defaults to 5")
    parser.add_argument("--slack_webhook",
                        default=os.getenv('SLACK_WEBHOOK'),
                        help="Webhook to use, determines what channel to post to")
    parser.add_argument("--post_to_slack",
                        default=True,
                        help="Optionally turn off posting messages to slack")

    args = parser.parse_args()
    work = ECRScanChecker(args.ecr_pushed_date_inclusive,
                          args.result_limit, args.search)
    work.recursive_wait(args.tag)
    work.recursive_check_make_report(args.tag)
    if args.slack_webhook is None:
        print("No slack webhook provided, skipping post of results to slack")
    if args.post_to_slack == True and args.slack_webhook is not None:
        work.post_to_slack(args.slack_webhook)


if __name__ == "__main__":
    main()
