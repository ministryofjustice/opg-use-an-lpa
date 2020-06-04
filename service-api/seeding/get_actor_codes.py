import boto3
import argparse
import pprint
from boto3.dynamodb.conditions import Key
import dateutil.parser
from datetime import datetime
from datetime import date
import json
from requests_aws4auth import AWS4Auth
import requests
import os
pp = pprint.PrettyPrinter(indent=4)


class CodesExporter:
    aws_account_id = ''
    lpas_collection_url = ''
    aws_iam_session = ''
    environment = ''
    dynamodb = ''
    actor_codes_table = ''

    def __init__(self, environment):
        self.set_account_ids(environment)
        self.set_iam_role_session()
        self.set_lpas_collection_url()

        if self.environment == "local":
            self.create_dynamodb_resources_for_local()
        else:
            self.create_dynamodb_resources()

        self.set_aws_auth()

    def set_aws_auth(self):
        self.aws_auth = AWS4Auth(
            self.aws_iam_session['Credentials']['AccessKeyId'],
            self.aws_iam_session['Credentials']['SecretAccessKey'],
            'eu-west-1',
            'execute-api',
            session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def set_account_ids(self, environment):
        aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment

        self.aws_account_id = aws_account_ids.get(
            self.environment, "367815980639")

    def set_lpas_collection_url(self):
        if self.environment == "production":
            self.lpas_collection_url = "https://api.sirius.opg.digital/v1/use-an-lpa/lpas/"
        else:
            self.lpas_collection_url = "https://api.dev.sirius.opg.digital/v1/use-an-lpa/lpas/"

    def set_iam_role_session(self):
        role_arn = 'arn:aws:iam::{0}:role/operator'.format(
            self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        self.aws_iam_session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='exporting_actor_codes',
            DurationSeconds=900
        )

    def create_dynamodb_resources(self):
        dynamodb = boto3.resource(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken']
        )
        self.actor_codes_table = dynamodb.Table(
            '{}-ActorCodes'.format(self.environment))

    def create_dynamodb_resources_for_local(self):
        dynamodb = boto3.resource(
            "dynamodb",
            region_name="eu-west-1",
            aws_access_key_id="",
            aws_secret_access_key="",
            aws_session_token="",
            endpoint_url="http://localhost:4569"
        )
        self.actor_codes_table = dynamodb.Table('ActorCodes')

    def scan_table(self):
        scan_kwargs = {
            'FilterExpression': Key('Active').eq(True),
            'Limit': 10
        }
        done = False
        start_key = None
        while not done:
            if start_key:
                scan_kwargs['ExclusiveStartKey'] = start_key
            response = self.actor_codes_table.scan(**scan_kwargs)
            self.process_actor_codes(response.get('Items', []))
            start_key = response.get('LastEvaluatedKey', None)
            done = start_key is None

    def update_with_sirius_data(self, sirius_uid, actor_id):
        actor_uid = ""
        actor_dob = ""
        response = self.call_api_gateway(sirius_uid)
        actors = response["attorneys"]
        actors.append(response["donor"])
        for actor in actors:
            if actor["id"] == actor_id:
                actor_uid = actor["uId"]
                actor_dob = actor["dob"]
                return dict(ActorUid=actor_uid, ActorDob=actor_dob)

    def convert_date_to_epoch(self, input_date):
        isoparse = dateutil.parser.isoparse(input_date)
        epoch = isoparse.timestamp()
        return epoch

    def call_api_gateway(self, lpa_id):
        method = 'GET'
        headers = {}
        body = ''
        url = str(self.lpas_collection_url+lpa_id)
        response = requests.request(
            method, url, auth=self.aws_auth, data=body, headers=headers)
        response_json = json.loads(response.text)
        return response_json

    def process_actor_codes(self, actor_codes):
        today = date.today().isoformat()
        for actor_code in actor_codes:
            sirius_data = self.update_with_sirius_data(
                actor_code["SiriusUid"],
                actor_code["ActorLpaId"]
            )
            expiry_epoch = self.convert_date_to_epoch(actor_code["Expires"])
            # Some loops on demo are not returning actor data. Why?
            if sirius_data is not None:
                output_dict = {
                    "code": actor_code["ActorCode"],
                    "active": actor_code["Active"],
                    "actor": sirius_data["ActorUid"],
                    "last_updated_date": today,
                    "lpa": actor_code["SiriusUid"],
                    "dob": sirius_data["ActorDob"],
                    "expiry_date": expiry_epoch,
                    "generated_date": today,
                    "status_details": "Imported"
                }
                print(output_dict)


def main():
    parser = argparse.ArgumentParser(
        description="Add or remove your host's IP address to the viewer and actor loadbalancer ingress rules.")
    parser.add_argument("-e", type=str, default="local",
                        help="The environment to get actor codes for.")
    args = parser.parse_args()
    work = CodesExporter(args.e)
    work.scan_table()


if __name__ == "__main__":
    main()
