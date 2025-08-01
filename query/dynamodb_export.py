from datetime import datetime
import calendar
import argparse
import boto3
import re
import csv
from time import sleep


class DynamoDBExporterAndQuerier:
    athena_database_name = "ual"
    aws_dynamodb_client = ""
    aws_kms_client = ""
    environment_details = ""
    export_time = ""

    def __init__(self, environment):
        self.tables = {
            "Stats": None,
            "ActorCodes": None,
            "ActorUsers": None,
            "ViewerCodes": None,
            "ViewerActivity": None,
            "UserLpaActorMap": None,
        }

        self.table_ddl_files = {
            "tables/stats.ddl": "Stats",
            "tables/actor_codes.ddl": "ActorCodes",
            "tables/actor_users.ddl": "ActorUsers",
            "tables/viewer_codes.ddl": "ViewerCodes",
            "tables/viewer_activity.ddl": "ViewerActivity",
            "tables/user_lpa_actor_map.ddl": "UserLpaActorMap",
        }

        self.environment_details = self.set_environment_details(environment)

        aws_iam_session = self.set_iam_role_session()

        self.aws_dynamodb_client = self.get_aws_client("dynamodb", aws_iam_session)

        self.aws_kms_client = self.get_aws_client("kms", aws_iam_session)

        self.aws_athena_client = self.get_aws_client("athena", aws_iam_session)

        self.kms_key_id = self.get_kms_key_id(
            "dynamodb-exports-{}".format(self.environment_details["account_name"])
        )
        self.export_bucket_name = "use-a-lpa-dynamodb-exports-{}".format(
            self.environment_details["account_name"]
        )

    def set_date_range(self, start, end):
        self.start_date = start
        self.end_date = end
        print(
            f"Queries will be run for date range {self.start_date} to {self.end_date}"
        )

    def set_default_date_range(self):
        today = datetime.today()
        days_in_mo = calendar.monthrange(today.year, today.month)
        self.start_date = f"{today.year}-{today.month}-01"
        self.end_date = f"{today.year}-{today.month}-{days_in_mo[1]}"
        print(
            f"Queries will be run for date range {self.start_date} to {self.end_date}"
        )

    @staticmethod
    def get_aws_client(client_type, aws_iam_session, region="eu-west-1"):
        client = boto3.client(
            client_type,
            region_name=region,
            aws_access_key_id=aws_iam_session["Credentials"]["AccessKeyId"],
            aws_secret_access_key=aws_iam_session["Credentials"]["SecretAccessKey"],
            aws_session_token=aws_iam_session["Credentials"]["SessionToken"],
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

    def set_iam_role_session(self):
        if self.environment_details["name"] == "production":
            role_arn = "arn:aws:iam::{}:role/db-analysis".format(
                self.environment_details["account_id"]
            )
        else:
            role_arn = "arn:aws:iam::{}:role/operator".format(
                self.environment_details["account_id"]
            )

        sts = boto3.client(
            "sts",
            region_name="eu-west-1",
        )

        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName="exporting_dynamodb_tables_to_s3",
            DurationSeconds=900,
        )
        return session

    def get_kms_key_id(self, kms_key_alias):
        response = self.aws_kms_client.describe_key(
            KeyId="alias/{}".format(kms_key_alias),
        )
        return response["KeyMetadata"]["KeyId"]

    def check_dynamo_export_status(self):
        overallCompleted = False
        print(
            "Waiting for DynamoDb export to be complete ( if run with Athena only option, this is just checking the previous export is complete )"
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
            sleep(10)
        print("\n")
        print("DynamoDB export is complete")

    def export_all_dynamo_tables(self):
        for table in self.tables.keys():
            table_arn = self.get_table_arn(
                "{}-{}".format(self.environment_details["name"], table)
            )
            s3_prefix = "{}-{}".format(self.environment_details["name"], table)

            self.export_dynamo_table(table_arn, self.export_bucket_name, s3_prefix)

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

    def get_dynamo_export_status(self, table):
        table_arn = self.get_table_arn(
            "{}-{}".format(self.environment_details["name"], table)
        )
        s3_prefix = "{}-{}".format(self.environment_details["name"], table)

        response = self.aws_dynamodb_client.list_exports(
            TableArn=table_arn, MaxResults=1
        )
        completed = True
        for export in response["ExportSummaries"]:
            export_arn_hash = export["ExportArn"].rsplit("/", 1)[-1]
            s3_path = "s3://{}/{}/AWSDynamoDB/{}/data/".format(
                self.export_bucket_name, s3_prefix, export_arn_hash
            )
            if export["ExportStatus"] != "COMPLETED":
                completed = False

        self.tables[table] = s3_path

        return completed

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

        if outputFileName:
            self.output_athena_results(results, outputFileName)

    def output_athena_results(self, results, outputFileName):
        with open(
            f"results/{outputFileName}-{self.start_date}-{self.end_date}.csv",
            "w",
            newline="",
        ) as outFile:
            wr = csv.writer(outFile, quoting=csv.QUOTE_ALL)
            for row in results:
                outputRow = ""
                csvRow = []
                for field in row["Data"]:
                    cell = (list)(field.values())
                    if cell:
                        outputRow = f"{outputRow} | {cell[0]}"
                        csvRow.append(cell[0])
                    else:
                        outputRow = f"{outputRow} | "
                        csvRow.append("")

                print(outputRow)
                wr.writerow(csvRow)

    def get_expired_viewed_access_codes(self):
        sql_string = f'SELECT distinct va.item.viewerCode.s as ViewedCode, va.item.viewedby.s as Organisation, vc.item.SiriusUid.s as "LPA Reference Number" FROM "ual"."viewer_activity" as va, "ual"."viewer_codes" as vc WHERE va.item.viewerCode = vc.item.viewerCode AND date_add(\'day\', -30, vc.item.expires.s) BETWEEN date(\'{self.start_date}\') AND date(\'{self.end_date}\') ORDER by Organisation;'
        self.run_athena_query(sql_string, outputFileName="ExpiredViewedAccessCodes")

    def get_expired_unviewed_access_codes(self):
        sql_string = f'SELECT vc.item.viewerCode.s as ViewerCode, vc.item.organisation.s as Organisation, vc.item.SiriusUid.s as "LPA Reference Number" FROM "ual"."viewer_codes" as vc WHERE vc.item.viewerCode.s not in (SELECT va.item.viewerCode.s FROM "ual"."viewer_activity" as va) AND date_add(\'day\', -30, vc.item.expires.s) BETWEEN date(\'{self.start_date}\') AND date(\'{self.end_date}\') ORDER BY vc.item.viewerCode.s'
        self.run_athena_query(sql_string, outputFileName="ExpiredUnviewedAccessCodes")

    def get_count_of_viewed_access_codes(self):
        sql_string = f"SELECT COUNT(*) FROM \"ual\".\"viewer_activity\" WHERE Item.Viewed.S BETWEEN date('{self.start_date}') AND date('{self.end_date}');"
        self.run_athena_query(
            sql_string,
            outputFileName="CountofViewedAccessCodes",
        )

    def get_count_of_created_access_codes(self):
        sql_string = f"SELECT COUNT(*) FROM \"ual\".\"viewer_codes\" WHERE Item.Added.S BETWEEN date('{self.start_date}') AND date('{self.end_date}');"
        self.run_athena_query(
            sql_string,
            outputFileName="CountofCreatedAccessCodes",
        )

    def get_count_of_expired_access_codes(self):
        sql_string = f"SELECT COUNT(*) FROM \"viewer_codes\" as vc WHERE date_add('day', -30, vc.item.expires.s) BETWEEN date('{self.start_date}') AND date('{self.end_date}');"
        self.run_athena_query(
            sql_string,
            outputFileName="CountofExpiredAccessCodes",
        )

    def get_organisations_field(self):
        sql_string = f"SELECT a.Item.ViewerCode.S as viewercode, a.Item.Organisation.S as organisation, b.Item.ViewedBy.S as viewedby, a.Item.Added.S as dateadded from viewer_codes a left join viewer_activity b on a.Item.ViewerCode.S = b.Item.ViewerCode.S where date_add('day', -30, a.Item.Added.s) BETWEEN date('{self.start_date}') AND date('{self.end_date}');"
        self.run_athena_query(
            sql_string,
            outputFileName="OrganisationsField",
        )

    def get_count_of_lpas_for_users(self):
        sql_string = f"SELECT countofLpasForUser as noOfLpas, count(countofLpasForUser) AS noOfUsersWithThisNoOfLpas from (SELECT count(item.userid.s) AS countofLpasForUser, item.userid.s from user_lpa_actor_map group by item.userid.s) as subquery group by countofLpasForUser order by countofLpasForUser"
        self.run_athena_query(
            sql_string,
            outputFileName="CountOfLpasForUsersWithSomeLpas",
        )

    def get_count_of_users_with_no_lpas(self):
        sql_string = f"SELECT COUNT(item.id.s) from actor_users where item.id.s not in (select item.userid.s from user_lpa_actor_map where item.userid is not null)"
        self.run_athena_query(
            sql_string,
            outputFileName="CountOfUsersWithNoLpas",
        )

    def get_duplicate_users_with_same_email(self):
            sql_string = f"SELECT a.Item.id.S as User_Id,a.Item.email.S as User_Email,COUNT(b.Item.SiriusUid) AS Lpa_Count FROM actor_users a LEFT JOIN user_lpa_actor_map b ON a.Item.id.S = b.Item.UserId.S WHERE a.Item.email.S IN (SELECT Item.email.S FROM actor_users GROUP BY Item.email.S HAVING COUNT(*) > 1) GROUP BY a.Item.email.S, a.Item.id.S ORDER BY a.Item.email.S"
            self.run_athena_query(
                sql_string,
                outputFileName="DuplicateUsersWithSameEmail",
            )

    def get_lpa_duplicate_users_with_same_email(self):
        sql_string = f"SELECT  a.Item.id.S as User_Id, a.Item.email.S as User_Email,b.Item.SiriusUid.S as Lpa_Id FROM actor_users a LEFT JOIN user_lpa_actor_map b ON a.Item.id.S = b.Item.UserId.S WHERE a.Item.email.S IN (SELECT Item.email.S FROM actor_users GROUP BY Item.email.S HAVING COUNT(*) > 1) GROUP BY a. Item.email.S, a.Item.id.S,b.Item.SiriusUid.S ORDER BY a.Item.email.S"
        self.run_athena_query(
            sql_string,
            outputFileName="LpasForDuplicateUsersWithSameEmail",
        )

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
        help="Output json data instead of plaintext to terminal",
    )
    parser.add_argument(
        "--reload_athena_and_query",
        dest="reload_athena_and_query_flag",
        action="store_const",
        const=True,
        default=False,
        help="Reload Athena and run query, assuming DynamoDb export has already run",
    )
    parser.add_argument(
        "--athena_query_only",
        dest="athena_query_only_flag",
        action="store_const",
        const=True,
        default=False,
        help="Only run the Athena query, assuming that DynamoDb export and load into Athena has already run",
    )
    parser.add_argument(
        "--start_date", default="", help="Start date in the form YYYY-MM-DD"
    )
    parser.add_argument(
        "--end_date", default="", help="End date in the form YYYY-MM-DD"
    )

    args = parser.parse_args()
    work = DynamoDBExporterAndQuerier(args.environment)

    if args.start_date and args.end_date:
        work.set_date_range(args.start_date, args.end_date)
    else:
        work.set_default_date_range()

    if args.check_only:
        work.check_dynamo_export_status()
        return

    # do the DynamoDb export, unless we've specified just Athena load and query, or just athena query
    if not args.reload_athena_and_query_flag and not args.athena_query_only_flag:
        work.export_all_dynamo_tables()

    # create the Athena tables,  unless we've specified query only
    if not args.athena_query_only_flag:
        work.check_dynamo_export_status()
        work.create_athena_tables()

    work.get_expired_viewed_access_codes()
    work.get_expired_unviewed_access_codes()
    work.get_count_of_viewed_access_codes()
    work.get_count_of_created_access_codes()
    work.get_count_of_expired_access_codes()
    work.get_organisations_field()
    work.get_count_of_lpas_for_users()
    work.get_count_of_users_with_no_lpas()
    work.get_duplicate_users_with_same_email()
    work.get_lpa_duplicate_users_with_same_email()


if __name__ == "__main__":
    main()
