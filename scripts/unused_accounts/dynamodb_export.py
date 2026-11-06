import argparse
import boto3
import re
from time import sleep


class DynamoDBExporterAndQuerier:
    athena_database_name = "ual"
    aws_dynamodb_client = ""
    aws_kms_client = ""
    environment_details = ""
    export_time = ""

    def __init__(self, environment):
        self.tables = {
            "ActorUsers": None,
            "UserLpaActorMap": None,
        }
        self.export_arns = {}

        self.table_ddl_files = {
            "tables/actor_users.ddl": "ActorUsers",
            "tables/user_lpa_actor_map.ddl": "UserLpaActorMap",
        }

        self.environment_details = self.set_environment_details(environment)

        self.aws_dynamodb_client = self.get_aws_client("dynamodb")

        self.aws_kms_client = self.get_aws_client("kms")

        self.aws_athena_client = self.get_aws_client("athena")

        self.kms_key_id = self.get_kms_key_id(
            "dynamodb-exports-{}".format(self.environment_details["account_name"])
        )
        self.export_bucket_name = "use-a-lpa-dynamodb-exports-{}".format(
            self.environment_details["account_name"]
        )

    @staticmethod
    def get_aws_client(client_type, region="eu-west-1"):
        client = boto3.client(
            client_type,
            region_name=region,
        )
        return client

    @staticmethod
    def set_environment_details(environment):
        aws_account_ids = {
            "production": "690083044361",
            "preproduction": "888228022356",
            "development": "367815980639",
        }

        aws_account_id = aws_account_ids.get(environment, "367815980639")

        if environment in aws_account_ids.keys():
            account_name = environment
        else:
            account_name = "development"

        response = {
            "name": environment.lower(),
            "account_name": account_name.lower(),
            "account_id": aws_account_id,
        }

        return response

    def get_kms_key_id(self, kms_key_alias):
        response = self.aws_kms_client.describe_key(
            KeyId="alias/{}".format(kms_key_alias),
        )
        return response["KeyMetadata"]["KeyId"]

    def check_dynamo_export_status(self):
        overallCompleted = False
        print(
            "Waiting for DynamoDB exports to complete"
        )
        while not overallCompleted:
            print(".", end="", flush=True)
            # assume all tables are completed until we encounter one that is not
            tablesCompleted = True
            for table in self.tables.keys():
                if not self.get_dynamo_export_status(table):
                    # we encountered an inconmplete table so they are not all complete
                    tablesCompleted = False
            overallCompleted = tablesCompleted
            if not overallCompleted:
                sleep(10)
        print("\n")
        print("DynamoDB export is complete")

    def export_all_dynamo_tables(self):
        for table in self.tables.keys():
            table_arn = self.get_table_arn(
                "{}-{}".format(self.environment_details["name"], table)
            )
            s3_prefix = "{}-{}".format(self.environment_details["name"], table)

            self.export_arns[table] = self.export_dynamo_table(
                table_arn, self.export_bucket_name, s3_prefix
            )

    def export_dynamo_table(self, table_arn, bucket_name, s3_prefix):
        print(f"exporting {table_arn} dynamoDb table")
        response = self.aws_dynamodb_client.export_table_to_point_in_time(
            TableArn=table_arn,
            S3Bucket=bucket_name,
            S3BucketOwner=self.environment_details["account_id"],
            S3Prefix=s3_prefix,
            S3SseAlgorithm="KMS",
            S3SseKmsKeyId=self.kms_key_id,
            ExportFormat="DYNAMODB_JSON",
        )
        return response["ExportDescription"]["ExportArn"]

    def select_existing_exports(self):
        for table in self.tables:
            table_arn = self.get_table_arn(
                f"{self.environment_details['name']}-{table}"
            )
            latest = None
            paginator = self.aws_dynamodb_client.get_paginator("list_exports")
            for page in paginator.paginate(TableArn=table_arn):
                for summary in page["ExportSummaries"]:
                    export = self.aws_dynamodb_client.describe_export(
                        ExportArn=summary["ExportArn"]
                    )["ExportDescription"]
                    if latest is None or export["StartTime"] > latest["StartTime"]:
                        latest = export
            if latest is None:
                raise RuntimeError(f"No DynamoDB exports found for {table_arn}")
            self.export_arns[table] = latest["ExportArn"]

    def get_dynamo_export_status(self, table):
        export_arn = self.export_arns[table]
        export = self.aws_dynamodb_client.describe_export(
            ExportArn=export_arn
        )["ExportDescription"]
        status = export["ExportStatus"]
        if status == "IN_PROGRESS":
            return False
        if status != "COMPLETED":
            raise RuntimeError(
                f"DynamoDB export {export_arn} has status {status}: "
                f"{export.get('FailureCode', '')} {export.get('FailureMessage', '')}"
            )

        export_id = export_arn.rsplit("/", 1)[-1]
        prefix = export.get("S3Prefix", "").strip("/")
        key = f"{prefix}/AWSDynamoDB/{export_id}/data/" if prefix else f"AWSDynamoDB/{export_id}/data/"
        self.tables[table] = f"s3://{export['S3Bucket']}/{key}"
        return True

    def get_table_arn(self, table_name):
        response = self.aws_dynamodb_client.describe_table(TableName=table_name)

        return response["Table"]["TableArn"]

    def drop_athena_database(self):
        query = f"DROP DATABASE IF EXISTS {self.athena_database_name} CASCADE;"
        self.run_athena_query(query, quiet=True)

    def create_athena_database(self):
        query = f"CREATE DATABASE IF NOT EXISTS {self.athena_database_name};"
        self.run_athena_query(query, quiet=True)

    def create_athena_tables(self):
        print("Re-creating Athena database and loading Athena tables")
        self.drop_athena_database()
        self.create_athena_database()

        for table_ddl in self.table_ddl_files.keys():
            exported_s3_location = self.tables[self.table_ddl_files[table_ddl]]
            self.create_athena_table(table_ddl, exported_s3_location)

    def create_athena_table(self, table_ddl, s3_location):
        with open(table_ddl) as ddl:
            rawQuery = ddl.read()
            searchStr = "'s3(.*)'"
            query = re.sub(searchStr, f"'{s3_location}'", rawQuery, flags=re.M)
            self.run_athena_query(query, quiet=True)

    def run_athena_query(self, query, outputFileName=None, quiet=False):
        if quiet != True:
            print("\n")
            print("Running Athena query : ")
            print(query)

        response = self.aws_athena_client.start_query_execution(
            QueryString=query,
            QueryExecutionContext={"Database": self.athena_database_name},
            ResultConfiguration={"OutputLocation": f"s3://{self.export_bucket_name}/"},
        )

        query_execution_id = response["QueryExecutionId"]
        while True:
            finish_state = self.aws_athena_client.get_query_execution(
                QueryExecutionId=query_execution_id
            )["QueryExecution"]["Status"]["State"]
            if finish_state == "RUNNING" or finish_state == "QUEUED":
                sleep(10)
            else:
                break

        assert finish_state == "SUCCEEDED", f"query state is {finish_state}"

        response = self.aws_athena_client.get_query_results(
            QueryExecutionId=query_execution_id, MaxResults=500
        )

        results = response["ResultSet"]["Rows"]

        while "NextToken" in response:
            response = self.aws_athena_client.get_query_results(
                QueryExecutionId=query_execution_id,
                MaxResults=500,
                NextToken=response["NextToken"],
            )
            results.extend(response["ResultSet"]["Rows"])

def main():
    parser = argparse.ArgumentParser(description="Exports DynamoDB tables to S3.")
    parser.add_argument(
        "--environment",
        default="demo",
        help="The environment to export DynamoDB data for",
    )
    parser.add_argument(
        "--check_exports",
        dest="check_only",
        action="store_const",
        const=True,
        default=False,
        help="Wait for the latest existing export of each table without starting new exports",
    )

    args = parser.parse_args()

    work = DynamoDBExporterAndQuerier(args.environment)

    if args.check_only:
        work.select_existing_exports()
        work.check_dynamo_export_status()
        return

    work.export_all_dynamo_tables()
    work.check_dynamo_export_status()
    work.create_athena_tables()

if __name__ == "__main__":
    main()
