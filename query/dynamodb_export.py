import boto3
import datetime
import argparse

class DynamoDBExporter:
    aws_dynamodb_client = ''
    aws_kms_client = ''
    environment_details = ''
    export_time = ''
    tables = ''

    def __init__(self, environment):
        self.export_time = datetime.datetime.now()

        self.tables = [
            "ActorCodes",
            "ActorUsers",
            "ViewerCodes",
            "ViewerActivity",
            "UserLpaActorMap",
        ]

        self.environment_details = self.set_environment_details(environment)
        print(self.environment_details)

        # self.kms_key_id = self.get_kms_key_id(
        #   'dynamodb-exports-{}'.format(
        #     self.environment_details['account_name'])
        #   )

        aws_iam_session = self.set_iam_role_session()

        self.aws_dynamodb_client = self.get_aws_client('dynamodb', aws_iam_session)
        self.aws_kms_client = self.get_aws_client('kms', aws_iam_session)

    def get_aws_client(self, client_type, aws_iam_session, region="eu-west-1"):
        client = boto3.client(
            client_type,
            region_name=region,
            aws_access_key_id=aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=aws_iam_session['Credentials']['SessionToken'])

        return client


    def set_environment_details(self, environment):
        aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }

        aws_account_id = aws_account_ids.get(
            environment, "367815980639")

        if environment in aws_account_ids.keys():
            account_name = environment
        else:
            account_name = 'development'

        response = {
            'name': environment,
            'account_name': account_name,
            'account_id': aws_account_id,
        }

        return response

    def set_iam_role_session(self):
        if self.environment_details['name'] == "production":
            role_arn = 'arn:aws:iam::{}:role/read-only-db'.format(
                self.environment_details['account_id'])
        else:
            role_arn = 'arn:aws:iam::{}:role/operator'.format(
                self.environment_details['account_id'])

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )

        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='exporting_dynamodb_tables_to_s3',
            DurationSeconds=900
        )
        return session

    def get_kms_key_id(self, kms_key_alias):
        response = self.aws_kms_client.describe_key(
            KeyId='alias/{}'.format(kms_key_alias),
        )
        return response['KeyMetadata']['KeyId']

    def export_table_to_point_in_time(self):
        print(self.export_time)
        for table in self.tables:
            table_arn = self.get_table_arn('{}-{}'.format(
              self.environment_details['name'],
              table)
              )
            print(table_arn)

            # response = self.aws_dynamodb_client.export_table_to_point_in_time(
            #     TableArn=table_arn,
            #     ExportTime=self.export_time,
            #     # ClientToken='string',
            #     S3Bucket='use-a-lpa-dynamodb-exports-{}'.format(
            #         self.environment_details['account_name']),
            #     S3BucketOwner=self.environment_details['account_id'],
            #     S3Prefix='{}-{}'.format(
            #         self.export_time,
            #         table),
            #     S3SseAlgorithm='KMS',
            #     S3SseKmsKeyId=self.kms_key_id,
            #     ExportFormat='DYNAMODB_JSON'
            # )
            # print(response)

    def get_table_arn(self, table_name):
        response = self.aws_dynamodb_client.describe_table(
            TableName=table_name
        )

        return response['Table']['TableArn']


def main():
    parser = argparse.ArgumentParser(
        description="Exports DynamoDB tables to S3.")
    parser.add_argument("--environment",
                        default="demo",
                        help="The environment to get organistion names for")

    args = parser.parse_args()
    work = DynamoDBExporter(
        args.environment)
    work.export_table_to_point_in_time()


if __name__ == "__main__":
    main()
