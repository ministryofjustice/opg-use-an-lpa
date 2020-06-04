import argparse
import os
import boto3
import json
import datetime


class LpaCodesSeeder:
    input_json_path = ''
    aws_account_id = ''
    aws_iam_session = ''
    dynamodb = ''

    def __init__(self, input_json, environment, docker_mode):
        self.input_json_path = input_json
        self.environment = environment
        if self.environment == 'local':
            self.create_dynamodb_resources_for_local(docker_mode)
        else:
            self.set_account_id()
            self.set_iam_role_session()
            self.create_dynamodb_resources()

        self.lpa_codes_table = self.dynamodb.Table(
            'lpa-codes-{}'.format(self.environment))

    def set_account_id(self):
        self.aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }

        self.aws_account_id = self.aws_account_ids.get(
            self.environment, "367815980639")

    def set_iam_role_session(self):
        role_arn = 'arn:aws:iam::{0}:role/operator'.format(
            self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        self.aws_iam_session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='importing_actor_codes',
            DurationSeconds=900
        )

    def create_dynamodb_resources(self):
        self.dynamodb = boto3.resource(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken']
        )

    def create_dynamodb_resources_for_local(self, docker_mode):
        if docker_mode:
            url = "host.docker.internal"
        else:
            url = "localhost"
        self.dynamodb = boto3.resource(
            "dynamodb",
            region_name="eu-west-1",
            aws_access_key_id="",
            aws_secret_access_key="",
            aws_session_token="",
            endpoint_url="http://{}:8000".format(url)
        )

    def put_actor_codes(self):
        with open(self.input_json_path) as f:
            actorLpaCodes = json.load(f)

        for actorLpaCode in actorLpaCodes:
            print(actorLpaCode)
            self.lpa_codes_table.put_item(
                Item=actorLpaCode,
            )
            response = self.lpa_codes_table.get_item(
                Key={'code': actorLpaCode['code']}
            )
            print(response)


def main():
    parser = argparse.ArgumentParser(
        description="Put actor codes into the lpa-codes API service.")
    parser.add_argument("-e", type=str, default="local",
                        help="The environment to push actor codes to.")
    parser.add_argument("-f", nargs='?', default="./seeding_lpa_codes.json", type=str,
                        help="Path to config file produced by terraform")
    parser.add_argument("-d", action='store_true', default=False,
                        help="Set to true if running inside a Docker container")
    args = parser.parse_args()

    work = LpaCodesSeeder(args.f, args.e, args.d)
    work.put_actor_codes()


if __name__ == "__main__":
    main()
