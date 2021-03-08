import argparse
import boto3
from botocore.exceptions import ClientError
from datetime import date
import json


class LpaCodesSeeder:
  input_json_path = ''
  aws_account_id = ''
  environment = ''
  aws_iam_session = ''
  lpa_codes_table = ''
  aws_dynamodb_client = ''

  def __init__(self, input_json, environment, iam_role_name):
    self.input_json_path = input_json
    self.environment = environment.lower()
    self.lpa_codes_table = 'lpa-codes-{}'.format(self.environment)

    self.set_account_id()
    self.set_iam_role_session(iam_role_name)

    self.aws_dynamodb_client = boto3.client(
      'dynamodb',
      region_name='eu-west-1',
      aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
      aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
      aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

  def set_account_id(self):
    aws_account_ids = {
      'integration': "288342028542",
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
      RoleSessionName='removing_actor_codes',
      DurationSeconds=900
    )

  def get_actor_codes(self):
    paginator = self.aws_dynamodb_client.get_paginator("scan")
    for page in paginator.paginate(
        TableName=self.lpa_codes_table,
        ):
      yield from page["Items"]

  def get_actor_code_item(self, item):
    response = self.aws_dynamodb_client.get_item(
      TableName=self.lpa_codes_table,
      Key={'code': {'S': item}}
    )
    return response

  def update_actor_code_item(self, item):
    today = str(date.today())
    try:
      response = self.aws_dynamodb_client.update_item(
        TableName=self.lpa_codes_table,
        Key = {'code': {'S': item}},
        ConditionExpression = "active = :condactive AND status_details = :condstatus",
        UpdateExpression = "set active = :active, last_updated_date = :date, status_details = :status",
        ExpressionAttributeValues={
          ":condactive": {"BOOL": True},
          ":condstatus": {"S": "Generated"},
          ":active": {"BOOL": False},
          ":date": {"S": today},
          ":status": {"S": "Deactivated"}
        },
        ReturnValues='ALL_NEW'
      )
      return response
    except ClientError as e:
      if e.response['Error']['Code']=='ConditionalCheckFailedException':
          print(e.response['Error'])

  def deactivate_actor_codes(self):
    actor_codes = self.get_actor_codes()
    with open(self.input_json_path) as f:
      lpas = json.load(f)

    for code in actor_codes:
      if code['lpa']['S'] in lpas:
        print(
          json.dumps(
            self.get_actor_code_item(code['code']['S']),
            indent=4
          )
        )
        print(
          json.dumps(
            self.update_actor_code_item(code['code']['S']),
            indent=4
          )
        )

def main():
  parser = argparse.ArgumentParser(
    description="Put actor codes into the lpa-codes API service.")
  parser.add_argument("-e", type=str, default="integration",
                      help="The environment to dactive actor codes in.")
  parser.add_argument("-f", default="./lpa_cases_to_deactivate_actor_codes_on.json", type=str,
                      help="Path to the json file listing LPAs.")
  parser.add_argument("-r", default="operator", type=str,
                      help="IAM role name to assume when pushing actor codes.")
  args = parser.parse_args()

  work = LpaCodesSeeder(args.f, args.e, args.r)
  work.deactivate_actor_codes()


if __name__ == "__main__":
  main()
