import argparse
import os
import sys
import logging
import json
import boto3
from requests_aws4auth import AWS4Auth
import requests
logging.basicConfig(encoding='utf-8', level=logging.INFO)


class APIGatewayCaller:
    aws_account_id = ''
    api_gateway_url = ''
    aws_iam_role = ''
    aws_iam_session = ''
    aws_auth = ''

    def __init__(self, target_production, dev_endpoint_url):
        self.choose_target_gateway(target_production, dev_endpoint_url)
        self.aws_iam_role = os.getenv('AWS_IAM_ROLE')
        self.set_iam_role_session()
        self.aws_auth = AWS4Auth(
            self.aws_iam_session['Credentials']['AccessKeyId'],
            self.aws_iam_session['Credentials']['SecretAccessKey'],
            'eu-west-1',
            'execute-api',
            session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def choose_target_gateway(self, target_production, dev_endpoint_url):
        if target_production:
            self.aws_account_id = '690083044361'
            self.api_gateway_url = 'https://lpa.api.opg.service.justice.gov.uk/v1/use-an-lpa/lpas/'
        else:
            self.aws_account_id = '367815980639'
            self.api_gateway_url = f'https://{dev_endpoint_url}/v1/use-an-lpa/lpas/'
        logging.info('targetting endpoint at: %s', self.api_gateway_url)

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/ci'
        else:
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/operator'

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='calling_api_gateway',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def call_api_gateway(self, lpa_id):
        method = 'GET'
        headers = {}
        body = ''
        url = str(self.api_gateway_url+lpa_id)
        response = requests.request(
            method, url, auth=self.aws_auth, data=body, headers=headers)
        logging.info('status code: %s', response.status_code)
        if response.status_code == 500:
            error_message = json.loads(response.text)[
                "body"]["error"]["message"] or "unknown error"
            logging.warning(
                'response: %s', error_message)
            sys.exit(1)
        return response.text


def main():
    parser = argparse.ArgumentParser(
        description="Look up LPA IDs on the Sirius API Gateway.")

    parser.add_argument("--dev_endpoint_url", type=str,
                        default='integration.dev.lpa.api.opg.service.justice.gov.uk',
                        help="endpoint other than integration to use for testing")
    parser.add_argument("lpa_id", type=str,
                        help="LPA ID to look up in API Gateway")
    parser.add_argument('--production', dest='target_production', action='store_const',
                        const=True, default=False,
                        help='target the production sirius api gateway')

    args = parser.parse_args()
    work = APIGatewayCaller(args.target_production, args.dev_endpoint_url)
    lpa_data = work.call_api_gateway(args.lpa_id)
    print(lpa_data)


if __name__ == "__main__":
    main()
