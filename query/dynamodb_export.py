import datetime
import argparse
import boto3
from time import sleep


class DynamoDBExporter:
    athena_database_name = "ual"
    athena_results_bucket = "use-a-lpa-dynamodb-exports"
    aws_dynamodb_client = ''
    aws_kms_client = ''
    environment_details = ''
    export_time = ''

    def __init__(self, environment):
        self.tables = [
            "ActorCodes",
            "ActorUsers",
            "ViewerCodes",
            "ViewerActivity",
            "UserLpaActorMap",
        ]

        self.environment_details = self.set_environment_details(environment)

        aws_iam_session = self.set_iam_role_session()

        self.aws_dynamodb_client = self.get_aws_client(
          'dynamodb',
          aws_iam_session)

        self.aws_kms_client = self.get_aws_client(
          'kms',
          aws_iam_session)

        self.aws_athena_client = self.get_aws_client(
          'athena',
          aws_iam_session)

        self.kms_key_id = self.get_kms_key_id(
          'dynamodb-exports-{}'.format(
            self.environment_details['account_name'])
          )

    @staticmethod
    def get_aws_client(client_type, aws_iam_session, region="eu-west-1"):
        client = boto3.client(
            client_type,
            region_name=region,
            aws_access_key_id=aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=aws_iam_session['Credentials']['SessionToken'])
        return client

    @staticmethod
    def set_environment_details(environment):
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
            'name': environment.lower(),
            'account_name': account_name.lower(),
            'account_id': aws_account_id,
        }

        return response

    def set_iam_role_session(self):
        if self.environment_details['name'] == "production":
            role_arn = 'arn:aws:iam::{}:role/db-analysis'.format(
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

    def check_export_status(self):
        overallCompleted = False
        while not overallCompleted:
            sleep(10)
            # assume all tables are completed until we encounter one that is not
            tablesCompleted = True
            for table in self.tables:
                if not self.get_export_status(table):
                    # we encountered an inconmplete table so they are not all complete
                    tablesCompleted = False
            overallCompleted = tablesCompleted


    def export_all_tables(self):
        for table in self.tables:
            table_arn = self.get_table_arn('{}-{}'.format(
              self.environment_details['name'],
              table)
              )
            bucket_name = 'use-a-lpa-dynamodb-exports-{}'.format(
                        self.environment_details['account_name'])
            s3_prefix = '{}-{}'.format(
                        self.environment_details['name'],
                        table)

            self.export_table(table_arn, bucket_name, s3_prefix)

    def export_table(self, table_arn, bucket_name, s3_prefix):
        print("exporting dynamoDb tables")
        response = self.aws_dynamodb_client.export_table_to_point_in_time(
            TableArn=table_arn,
            S3Bucket=bucket_name,
            S3BucketOwner=self.environment_details['account_id'],
            S3Prefix=s3_prefix,
            S3SseAlgorithm='KMS',
            S3SseKmsKeyId=self.kms_key_id,
            ExportFormat='DYNAMODB_JSON'
        )

    def get_export_status(self, table):
        table_arn = self.get_table_arn('{}-{}'.format(
          self.environment_details['name'],
          table)
          )
        bucket_name = 'use-a-lpa-dynamodb-exports-{}'.format(
                    self.environment_details['account_name'])
        s3_prefix = '{}-{}'.format(
                    self.environment_details['name'],
                    table)

        print('\n')
        print('DynamoDB Table ARN:',table_arn)
        print('S3 Bucket Name:', bucket_name)
        response = self.aws_dynamodb_client.list_exports(
        TableArn=table_arn,
        MaxResults=1
        )
        completed = True
        for export in response['ExportSummaries']:
            export_arn_hash = export['ExportArn'].rsplit('/', 1)[-1]
            s3_path = 's3://{}/{}/AWSDynamoDB/{}/data/'.format(
                bucket_name,
                s3_prefix,
                export_arn_hash
            )
            print('\t', export['ExportStatus'])
            if export['ExportStatus'] != "COMPLETED":
                completed = False

        print('completed is ')
        print(completed)

        return completed

    def get_table_arn(self, table_name):
        response = self.aws_dynamodb_client.describe_table(
            TableName=table_name
        )

        return response['Table']['TableArn']

    def create_athena_database(self ):
        response = self.aws_athena_client.start_query_execution(
            QueryString=f"CREATE DATABASE IF NOT EXISTS {self.athena_database_name};",
            ResultConfiguration={
                    "OutputLocation": f"s3://{self.athena_results_bucket}/"
            }
        )

        return response["QueryExecutionId"]

    def create_athena_table(self, table_ddl):
        with open(table_ddl) as ddl:
            query = ddl.read()
            response = self.aws_athena_client.start_query_execution(
                QueryString=query,
                QueryExecutionContext={
                    "Database": self.athena_database_name
                },
                ResultConfiguration={
                    "OutputLocation": f"s3://{self.athena_results_bucket}/"
                }
            )

            return response["QueryExecutionId"]

    def run_athena_query(self):
        self.create_athena_database()
        #table_ddl_files = ["tables/viewer_activity.ddl", "tables/viewer_codes.ddl", "tables/actor_users.ddl", "tables/user_lpa_actor_map.ddl"]
        table_ddl_files = ["tables/actor_users.ddl"]
        for table_ddl in table_ddl_files:
            query_execution_id = self.create_athena_table(table_ddl)
            sleep(10)
            print(f"Query execution id: {query_execution_id}")
            response = self.aws_athena_client.get_query_results(
                QueryExecutionId=query_execution_id,
                MaxResults=1
            )
            print(response)

def main():
    parser = argparse.ArgumentParser(
        description="Exports DynamoDB tables to S3.")
    parser.add_argument("--environment",
                        default="demo",
                        help="The environment to export DynamoDB data for")
    parser.add_argument('--check_exports', dest='check_only', action='store_const',
                        const=True, default=False,
                        help='Output json data instead of plaintext to terminal')
    parser.add_argument('--athena_only', dest='athena_only_flag', action='store_const',
                        const=True, default=False,
                        help='Only run the athena query not the DynamoDb export. Assume that has already run')

    args = parser.parse_args()
    work = DynamoDBExporter(
        args.environment)

    if args.check_only:
        work.check_export_status()
        return

    if not args.athena_only_flag:
        work.export_all_tables()

    work.check_export_status()
    work.run_athena_query()

if __name__ == "__main__":
    main()
