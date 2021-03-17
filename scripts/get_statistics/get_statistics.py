import argparse
import datetime
import json
import boto3
import os
from dateutil.relativedelta import relativedelta


class StatisticsCollector:
    aws_cloudwatch_client = ''
    aws_dynamodb_client = ''
    dynamodb_scan_paginator = ''
    environment = ''
    startdate = ''
    enddate = ''
    statistics = {}

    def __init__(self, environment, startdate, enddate):
        aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment
        aws_account_id = aws_account_ids.get(
            self.environment, "367815980639")

        aws_iam_session = self.set_iam_role_session(aws_account_id)
        self.aws_cloudwatch_client = boto3.client(
            'cloudwatch',
            region_name='eu-west-1',
            aws_access_key_id=aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=aws_iam_session['Credentials']['SessionToken'])

        self.aws_dynamodb_client = boto3.client(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=aws_iam_session['Credentials']['SessionToken'])

        self.dynamodb_scan_paginator = self.aws_dynamodb_client.get_paginator("scan")

        self.format_dates(startdate, enddate)

    def set_iam_role_session(self, aws_account_id):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/opg-use-an-lpa-ci'.format(
                aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{}:role/breakglass'.format(
                aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='getting_service_statistics',
            DurationSeconds=900
        )
        return session

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

    def get_metric_statistic(self, month_start, month_end, metric_name):
        response = self.aws_cloudwatch_client.get_metric_statistics(
            Namespace='{}_events'.format(self.environment),
            MetricName=metric_name,
            StartTime=month_start,
            EndTime=month_end,
            Period=3600,
            Statistics=['Sum'],
        )
        return response['Datapoints']


    def pagintated_get_total_counts_by_month(self, month_start, month_end, table_name, filter_exp):
        running_sum = 0

        for page in self.dynamodb_scan_paginator.paginate(
          TableName=table_name,
          FilterExpression=filter_exp,
          ExpressionAttributeValues={
            ':fromdate': {'S': str(month_start)},
            ':todate': {'S': str(month_end)}
            }
          ):
            running_sum += page['Count']

        return running_sum

    def sum_metrics(self, event):
        monthly_sum = {}
        total = 0
        for month_start in self.iterate_months():
            month_end = month_start + relativedelta(months=1)
            datapoints = self.get_metric_statistic(self.format_month(
                month_start), self.format_month(month_end), event)

            sum_value = int(sum(each['Sum'] for each in datapoints if each))
            monthly_sum[str(month_start)] = sum_value
            total = total + sum_value
        data = {}
        data['total'] = total
        data['monthly'] = monthly_sum

        return data

    def sum_dynamodb_counts(self, table_name, filter_expression):
        monthly_sum = {}
        for month_start in self.iterate_months():
            month_end = month_start + relativedelta(months=1)
            sum_value = self.pagintated_get_total_counts_by_month(
                self.format_month(month_start),
                self.format_month(month_end),
                table_name=table_name,
                filter_exp=filter_expression,
            )
            monthly_sum[str(
                month_start)] = sum_value
        data = {}
        data['total'] = sum(monthly_sum.values())
        data['monthly'] = monthly_sum

        return data


    def produce_json(self):
        self.statistics = {}
        self.statistics['statistics'] = {}
        stats=self.statistics['statistics']

        # Get statistics from Cloudwatch metric statistics

        event_codes = [
          'account_activated',
          'account_created',
          'account_deleted',
          'share_code_not_found',
          'older_lpa_success',
          'older_lpa_not_found',
          'older_lpa_does_not_match',
          'older_lpa_not_eligible',
          'older_lpa_has_activation_key'
        ]

        for event in event_codes:
          stats[event] = self.sum_metrics('{}_event'.format(event))

        # Get statistics from Dynamodb counts

        stats['lpas_added'] = self.sum_dynamodb_counts(
          table_name='{}-UserLpaActorMap'.format(self.environment),
          filter_expression='Added BETWEEN :fromdate AND :todate'
        )

        stats['viewer_codes_created'] = self.sum_dynamodb_counts(
          table_name='{}-ViewerCodes'.format(self.environment),
          filter_expression='Added BETWEEN :fromdate AND :todate'
          )

        stats['viewer_codes_viewed'] = self.sum_dynamodb_counts(
          table_name='{}-ViewerActivity'.format(self.environment),
          filter_expression='Viewed BETWEEN :fromdate AND :todate'
          )


    def print_json(self):
        print(json.dumps(self.statistics))

    def print_plaintext(self):
        plaintext = ""
        for statistic in self.statistics["statistics"]:
            plaintext += "*{0}*\n".format(statistic).upper()
            plaintext += "*Total for this reporting period:* {0}\n".format(self.statistics["statistics"][statistic]["total"])
            plaintext += "*Monthly Breakdown*\n"
            plaintext += "```\n"
            for key, value in self.statistics["statistics"][statistic]["monthly"].items():
                plaintext += "{0} {1} \n".format(key, str(value))
            plaintext += "```\n"

        print(plaintext)


def main():
    parser = argparse.ArgumentParser(
        description="Print whole month service statistics as JSON.")
    parser.add_argument("--environment",
                        default="production",
                        help="The environment to provide stats for")
    parser.add_argument("--startdate",
                        default="2020-07-17",
                        help="Where to start metric summing, defaults to launch of service")
    parser.add_argument("--enddate",
                        default="",
                        help="Where to end metric summing, defaults to today")
    parser.add_argument("--text", dest="plaintext_output", action="store_const",
                        const=True, default=False,
                        help="Output stats as a plaintext statement")

    args = parser.parse_args()
    work = StatisticsCollector(
        args.environment, args.startdate, args.enddate)

    work.produce_json()
    if args.plaintext_output :
        work.print_plaintext()
    else:
        work.print_json()
if __name__ == "__main__":
    main()
