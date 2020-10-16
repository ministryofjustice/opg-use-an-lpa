import boto3
import argparse
import datetime
from dateutil.relativedelta import relativedelta
import requests
import json
import os


class AccountsCreatedChecker:
    aws_account_id = ''
    aws_iam_session = ''
    aws_cloudwatch_client = ''
    aws_dynamodb_client = ''
    environment = ''
    startdate = ''
    enddate = ''
    account_created_monthly_totals = {}
    account_created_total = 0
    lpas_added_monthly_totals = {}
    lpas_added_total = 0
    viewer_codes_created_monthly_totals = {}
    viewer_codes_created_total = 0
    viewer_codes_viewed_monthly_totals = {}
    viewer_codes_viewed_total = 0
    json_output = ''

    def __init__(self, environment, startdate, enddate):
        self.aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment
        self.aws_account_id = self.aws_account_ids.get(
            self.environment, "367815980639")

        self.set_iam_role_session()
        self.aws_cloudwatch_client = boto3.client(
            'cloudwatch',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

        self.aws_dynamodb_client = boto3.client(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

        self.format_dates(startdate, enddate)

    def set_iam_role_session(self):
        role_arn = 'arn:aws:iam::{}:role/breakglass'.format(
            self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='checking_accounts_created_metric',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def format_dates(self, startdate, enddate):
        self.startdate = datetime.date.fromisoformat(
            startdate)
        if enddate == "":
            self.enddate = datetime.date.today()
        else:
            self.enddate = datetime.date.fromisoformat(enddate)
        assert self.startdate < self.enddate

    def format_month(self, date):
        return datetime.datetime.combine(date, datetime.datetime.min.time())

    def sum_account_created_metrics(self):
        for month_start in self.iterate_months():
            month_end = month_start + relativedelta(months=1)
            datapoints = self.get_account_created_metric_statistic(self.format_month(
                month_start), self.format_month(month_end))

            sum_value = int(sum(each['Sum'] for each in datapoints if each))

            self.account_created_monthly_totals[str(month_start)] = sum_value
            self.account_created_total = self.account_created_total + sum_value

    def sum_lpas_added_count(self):
        for month_start in self.iterate_months():
            month_end = month_start + relativedelta(months=1)
            sum_value = self.get_lpas_added_count(self.format_month(
                month_start), self.format_month(month_end))
            self.lpas_added_monthly_totals[str(month_start)] = sum_value

    def sum_viewer_codes_created_count(self):
        for month_start in self.iterate_months():
            month_end = month_start + relativedelta(months=1)
            sum_value = self.get_viewer_codes_created_count(self.format_month(
                month_start), self.format_month(month_end))
            self.viewer_codes_created_monthly_totals[str(
                month_start)] = sum_value

    def sum_viewer_codes_viewed_count(self):
        for month_start in self.iterate_months():
            month_end = month_start + relativedelta(months=1)
            sum_value = self.get_viewer_codes_viewed_count(self.format_month(
                month_start), self.format_month(month_end))
            self.viewer_codes_viewed_monthly_totals[str(
                month_start)] = sum_value

    def iterate_months(self):
        year = self.startdate.year
        month = self.startdate.month
        while True:
            current = datetime.date(year, month, 1)
            yield current
            if current.month == self.enddate.month and current.year == self.enddate.year:
                break
            else:
                month = ((month + 1) % 12) or 12
                if month == 1:
                    year += 1

    def get_account_created_metric_statistic(self, month_start, month_end):
        response = self.aws_cloudwatch_client.get_metric_statistics(
            Namespace='{}_events'.format(self.environment),
            MetricName='account_created_event',
            StartTime=month_start,
            EndTime=month_end,
            Period=3600,
            Statistics=['Sum'],
        )
        return response['Datapoints']

    def get_lpas_added_count(self, month_start, month_end):
        response = self.aws_dynamodb_client.scan(
            TableName='{}-UserLpaActorMap'.format(self.environment),
            FilterExpression='Added BETWEEN :fromdate AND :todate',
            ExpressionAttributeValues={':fromdate': {
                'S': str(month_start)}, ':todate': {'S': str(month_end)}}
        )
        return response['Count']

    def get_viewer_codes_created_count(self, month_start, month_end):
        response = self.aws_dynamodb_client.scan(
            TableName='{}-ViewerCodes'.format(self.environment),
            FilterExpression='Added BETWEEN :fromdate AND :todate',
            ExpressionAttributeValues={':fromdate': {
                'S': str(month_start)}, ':todate': {'S': str(month_end)}}
        )
        return response['Count']

    def get_viewer_codes_viewed_count(self, month_start, month_end):
        response = self.aws_dynamodb_client.scan(
            TableName='{}-ViewerActivity'.format(self.environment),
            FilterExpression='Viewed BETWEEN :fromdate AND :todate',
            ExpressionAttributeValues={':fromdate': {
                'S': str(month_start)}, ':todate': {'S': str(month_end)}}
        )
        return response['Count']

    def produce_json(self):
        self.sum_lpas_added_count()
        self.sum_viewer_codes_created_count()
        self.sum_viewer_codes_viewed_count()
        self.sum_account_created_metrics()

        statistics = {}
        statistics['statistics'] = {}

        statistics['statistics']['accounts_created'] = {}
        statistics['statistics']['accounts_created']['total'] = self.account_created_total
        statistics['statistics']['accounts_created']['monthly'] = self.account_created_monthly_totals

        statistics['statistics']['lpas_added'] = {}
        statistics['statistics']['lpas_added']['total'] = sum(
            self.lpas_added_monthly_totals.values())
        statistics['statistics']['lpas_added']['monthly'] = self.lpas_added_monthly_totals

        statistics['statistics']['viewer_codes_created'] = {}
        statistics['statistics']['viewer_codes_created']['total'] = sum(
            self.viewer_codes_created_monthly_totals.values())
        statistics['statistics']['viewer_codes_created']['monthly'] = self.viewer_codes_created_monthly_totals

        statistics['statistics']['viewer_codes_viewed'] = {}
        statistics['statistics']['viewer_codes_viewed']['total'] = sum(
            self.viewer_codes_viewed_monthly_totals.values())
        statistics['statistics']['viewer_codes_viewed']['monthly'] = self.viewer_codes_viewed_monthly_totals

        self.json_output = json.dumps(statistics)
        print(self.json_output)


def main():
    parser = argparse.ArgumentParser(
        description="Print the total accounts created. Starts from teh first of the month of the given start date.")
    parser.add_argument("--environment",
                        default="production",
                        help="The environment to provide stats for")
    parser.add_argument("--startdate",
                        default="2020-07-17",
                        help="Where to start metric summing, defaults to launch of service")
    parser.add_argument("--enddate",
                        default="",
                        help="Where to end metric summing, defaults to today")

    args = parser.parse_args()
    work = AccountsCreatedChecker(
        args.environment, args.startdate, args.enddate)
    work.produce_json()


if __name__ == "__main__":
    main()
