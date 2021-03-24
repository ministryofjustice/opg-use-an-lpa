import boto3
import datetime
import argparse
import json

class DynamoDBExporter:
    aws_account_id = ''
    aws_iam_session = ''
    aws_dynamodb_client = ''
    environment = ''
    export_time = ''
    tables = ''

    def __init__(self, environment):
        # self.export_time = datetime.datetime.now()

        self.tables = [
            "ActorCodes",
            "ActorUsers",
            "ViewerCodes",
            "ViewerActivity",
            "UserLpaActorMap",
        ]

        self.aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment
        self.aws_account_id = self.aws_account_ids.get(
            self.environment, "367815980639")

        self.set_iam_role_session()

        self.aws_dynamodb_client = boto3.client(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def set_iam_role_session(self):
        if self.environment == "production":
            role_arn = 'arn:aws:iam::{}:role/read-only-db'.format(
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
            RoleSessionName='exporting_dynamodbtables_to_s3',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def export_table_to_point_in_time(self):
        for table in self.tables:
            table_arn = self.get_table_arn('{}-{}'.format(self.environment, table))
            print(table_arn)

            # response = self.aws_dynamodb_client.export_table_to_point_in_time(
            #     TableArn=table_arn,
            #     ExportTime=self.export_time,
            #     ClientToken='string',
            #     S3Bucket='DataWarehouse{}'.format(table),
            #     S3BucketOwner='string',
            #     S3Prefix='string',
            #     S3SseAlgorithm='AES256'|'KMS',
            #     S3SseKmsKeyId='string',
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
