import boto3
import argparse
from boto3.dynamodb.conditions import Key
import dateutil.parser
from datetime import datetime
from datetime import date
import json
from requests_aws4auth import AWS4Auth
import requests


class CodesExporter:
    aws_account_id = ''
    lpas_collection_url = ''
    aws_iam_session = ''
    environment = ''
    dynamodb = ''
    today = ''
    count = 0
    actor_codes_table = ''
    json_output = ''

    def __init__(self, environment):
        self.set_account_ids(environment)
        self.set_iam_role_session()
        self.set_lpas_collection_url()
        self.today = date.today().isoformat()
        self.json_output = json.loads('[]')
        self.set_dynamodb_table()
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

    def set_dynamodb_table(self):
        if self.environment == "local":
            dynamodb = boto3.resource(
                "dynamodb",
                region_name="eu-west-1",
                aws_access_key_id="",
                aws_secret_access_key="",
                aws_session_token="",
                endpoint_url="http://localhost:4569"
            )
            self.actor_codes_table = dynamodb.Table('ActorCodes')
        else:
            dynamodb = boto3.resource(
                'dynamodb',
                region_name='eu-west-1',
                aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
                aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
                aws_session_token=self.aws_iam_session['Credentials']['SessionToken']
            )
            self.actor_codes_table = dynamodb.Table(
                '{}-ActorCodes'.format(self.environment))

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
                    "last_updated_date": self.today,
                    "lpa": actor_code["SiriusUid"],
                    "dob": sirius_data["ActorDob"],
                    "expiry_date": int(expiry_epoch),
                    "generated_date": self.today,
                    "status_details": "Imported"
                }
                print(output_dict)
                self.json_output.append(output_dict)
                self.count = self.count + 1

    def write_json_file(self):
        json_file_name = "/tmp/lpa_codes_{}_{}.json".format(
            self.environment, self.today)
        with open(json_file_name, 'w') as json_file:
            json.dump(self.json_output, json_file, indent=2)


def main():
    parser = argparse.ArgumentParser(
        description="Export Actor Codes from the legacy codes service and produce a json file.")
    parser.add_argument("-e", type=str, default="local",
                        help="The environment to get actor codes for.")
    args = parser.parse_args()
    work = CodesExporter(args.e)
    work.scan_table()
    work.write_json_file()
    print("exported {} actor codes".format(work.count))


if __name__ == "__main__":
    main()
