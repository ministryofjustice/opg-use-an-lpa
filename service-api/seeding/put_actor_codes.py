import argparse
import os
import boto3
import json
import datetime


class LpaCodesSeeder:
    input_json_path = ''
    aws_account_id = ''
    environment = ''
    aws_iam_session = ''
    lpa_codes_table = ''
    dynamodb = ''
    expires = ''

    def __init__(self, input_json, environment, docker_mode, iam_role_name):
        self.input_json_path = input_json
        self.environment = environment
        if self.environment == 'local':
            self.create_dynamodb_resources_for_local(docker_mode)
        else:
            self.set_account_id()
            self.set_iam_role_session(iam_role_name)
            self.create_dynamodb_resources()

        self.lpa_codes_table = self.dynamodb.Table(
            'lpa-codes-{}'.format(self.environment))


    def set_account_id(self):
        aws_account_ids = {
            'production': "649098267436",
            'preproduction': "492687888235",
            'development': "288342028542",
        }

        self.aws_account_id = aws_account_ids.get(
            self.environment, "288342028542")

    def set_iam_role_session(self, iam_role_name):
        role_arn = 'arn:aws:iam::{0}:role/{1}'.format(
            self.aws_account_id, iam_role_name)

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
        today = datetime.datetime.now()
        next_week = today + datetime.timedelta(days=7)
        last_week = today - datetime.timedelta(days=7)
        with open(self.input_json_path) as f:
            actorLpaCodes = json.load(f)

        for actorLpaCode in actorLpaCodes:
            if actorLpaCode['expiry_date'] == "valid":
                actorLpaCode['expiry_date'] = int(next_week.timestamp())
            else:
                actorLpaCode['expiry_date'] = int(last_week.timestamp())
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
                        help="Path to the json file of data to put.")
    parser.add_argument("-r", nargs='?', default="operator", type=str,
                        help="IAM role to assume when pushing actor codes.")
    parser.add_argument("-d", action='store_true', default=False,
                        help="Set to true if running inside a Docker container.")
    args = parser.parse_args()

    work = LpaCodesSeeder(args.f, args.e, args.d, args.r)
    work.put_actor_codes()


if __name__ == "__main__":
    main()
