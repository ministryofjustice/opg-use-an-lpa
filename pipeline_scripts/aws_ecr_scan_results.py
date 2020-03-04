import urllib.request
import boto3
import argparse
import json
import os
import sys
import pprint

pp = pprint.PrettyPrinter(indent=4)


class ECRScanChecker:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecr_client = ''
    aws_ecr_repository_path = ''
    failed_checks = 0
    images_to_check = []

    def __init__(self, config_file):
        self.images_to_check = [
            "api_app",
            "api_web",
            "front_app",
            "front_web",
            "pdf"
        ]
        self.aws_ecr_repository_path = 'use_an_lpa/'
        self.read_parameters_from_file(config_file)
        self.set_iam_role_session()
        self.aws_ecr_client = boto3.client(
            'ecr',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            self.aws_account_id = 311462405659

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

    def recursive_wait(self, tag):
        print("Waiting for ECR scans to complete...")
        for image in self.images_to_check:
            self.wait_for_scan_completion(image, tag)
        print("ECR image scans complete")

    def wait_for_scan_completion(self, image, tag):
        try:
            repository_name = self.aws_ecr_repository_path+image
            waiter = self.aws_ecr_client.get_waiter('image_scan_complete')
            waiter.wait(
                repositoryName=repository_name,
                imageId={
                    'imageTag': tag
                },
                maxResults=1,
                WaiterConfig={
                    'Delay': 5,
                    'MaxAttempts': 60
                }
            )
        except:
            print("Unable to return ECR image scan results for", tag)
            exit(1)

    def recursive_check(self, tag):
        print("Checking ECR scan results...")
        for image in self.images_to_check:
            if self.get_ecr_scan_findings(image, tag)[
                    "imageScanFindings"]["findings"] != []:
                print("found results for", self.aws_ecr_repository_path+image, ":")
                pp.pprint(self.get_ecr_scan_findings(image, tag)[
                    "imageScanFindings"]["findings"])
                self.failed_checks += 1

    def get_ecr_scan_findings(self, image, tag):
        repository_name = self.aws_ecr_repository_path+image
        response = self.aws_ecr_client.describe_image_scan_findings(
            repositoryName=repository_name,
            imageId={
                'imageTag': tag
            },
            maxResults=1
        )
        return response

    def pipeline_status(self):
        if self.failed_checks > 0:
            return "ECR scan results failure"


def main():
    parser = argparse.ArgumentParser(
        description="Check ECR Scan results for all service container images.")

    parser.add_argument("config_file_path",
                        nargs='?',
                        default="/tmp/cluster_config.json",
                        type=str,
                        help="Path to config file produced by terraform")
    parser.add_argument("--tag",
                        default="latest",
                        help="Image tag to check scan results for.")

    args = parser.parse_args()

    work = ECRScanChecker(args.config_file_path)

    work.recursive_wait(args.tag)
    work.recursive_check(args.tag)
    status = work.pipeline_status()
    os.environ['SCAN_FAILURE'] = status
    print(status)


if __name__ == "__main__":
    main()
