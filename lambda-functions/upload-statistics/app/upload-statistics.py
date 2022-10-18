import os
import boto3
import botocore
import datetime
from dateutil.relativedelta import relativedelta
import logging


class StatisticsCollector:
    aws_cloudwatch_client = ''
    aws_dynamodb_client = ''
    dynamodb_scan_paginator = ''
    metrics_list = []

    def __init__(self):
        self.environment = os.getenv('ENVIRONMENT')
        self.region = os.getenv('REGION')
        self.dynamodb_table_prefix = f'{self.environment}-' if self.environment != 'local' else ''
        self.aws_dynamodb_client = self.get_dynamodb_client()
        self.aws_cloudwatch_client = self.get_cloudwatch_client()

        self.dynamodb_scan_paginator = self.aws_dynamodb_client.get_paginator("scan")
        self.startdate = datetime.datetime.now() - datetime.timedelta(days=90)
        self.enddate = datetime.date.today()
        self.update_totals = False
        self.logger = logging.getLogger('statistics')
        self.logger.setLevel('INFO')

    @staticmethod
    def local_dev_response():
        return {
            "statistics": {
                "lpas_added": {
                    "monthly": {
                        "2022-07-01": 5,
                        "2022-08-01": 5,
                        "2022-09-01": 5,
                        "2022-10-01": 5
                    }
                }
            }
        }

    def get_cloudwatch_client(self):
        aws_iam_session = boto3.session.Session()
        if self.environment != 'local':
            cloudwatch_session = self.aws_cloudwatch_client = aws_iam_session.client(
                'cloudwatch',
                region_name=self.region
            )
        else:
            cloudwatch_session = None

        return cloudwatch_session

    def get_dynamodb_client(self):
        aws_iam_session = boto3.session.Session()
        if self.environment != 'local':
            dynamodb_session = self.aws_dynamodb_client = aws_iam_session.client(
                'dynamodb',
                region_name=self.region
            )
        else:
            dynamodb_session = self.aws_dynamodb_client = aws_iam_session.client(
                'dynamodb',
                endpoint_url='http://dynamodb-local:8000',
                region_name=self.region
            )
        return dynamodb_session

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
            Statistics=['Sum'])
        return response['Datapoints']

    def paginated_get_total_counts_by_month(self, month_start, month_end, table_name, filter_exp):
        running_sum = 0
        for page in self.dynamodb_scan_paginator.paginate(
          TableName=table_name,
          FilterExpression=filter_exp,
          ExpressionAttributeValues={
            ':fromdate': {'S': str(month_start)},
            ':todate': {'S': str(month_end)}
          }):
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
        data = {'total': total, 'monthly': monthly_sum}
        return data

    def list_metrics_for_environment(self):
        metrics_list = []
        response = self.aws_cloudwatch_client.list_metrics(
            Namespace='{}_events'.format(self.environment))
        for metric in response['Metrics']:
            metrics_list.append(metric['MetricName'])
        return metrics_list

    def sum_dynamodb_counts(self, table_name, filter_expression):
        monthly_sum = {}
        for month_start in self.iterate_months():
            month_end = month_start + relativedelta(months=1)
            sum_value = self.paginated_get_total_counts_by_month(
                self.format_month(month_start),
                self.format_month(month_end),
                table_name=table_name,
                filter_exp=filter_expression)
            monthly_sum[str(month_start)] = sum_value
        data = {'monthly': monthly_sum}
        return data

    def get_statistics(self):
        try:
            self.logger.info("=== Starting statistics collection ===")
            statistics = {'statistics': {}}
            message_prefix = "Getting statistics for metric:"
            # Get statistics from Cloudwatch metric statistics
            for metric in self.list_metrics_for_environment():
                self.logger.info(f"{message_prefix} {metric}")
                statistics['statistics'][metric] = self.sum_metrics(metric)

            # Get statistics from Dynamodb counts
            self.logger.info(f"{message_prefix} lpas_added")
            statistics['statistics']['lpas_added'] = self.sum_dynamodb_counts(
                table_name=f'{self.dynamodb_table_prefix}UserLpaActorMap',
                filter_expression='Added BETWEEN :fromdate AND :todate')

            self.logger.info(f"{message_prefix} viewer_codes_created")
            statistics['statistics']['viewer_codes_created'] = self.sum_dynamodb_counts(
                table_name=f'{self.dynamodb_table_prefix}ViewerCodes',
                filter_expression='Added BETWEEN :fromdate AND :todate')

            self.logger.info(f"{message_prefix} viewer_codes_viewed")
            statistics['statistics']['viewer_codes_viewed'] = self.sum_dynamodb_counts(
                table_name=f'{self.dynamodb_table_prefix}ViewerActivity',
                filter_expression='Viewed BETWEEN :fromdate AND :todate')
        except Exception:
            self.logger.info("Exception gathering data")
            return None

        return statistics

    def get_latest_month(self, first_item):
        highest_month_as_dt = datetime.datetime.strptime('1900-01', "%Y-%m")
        for month, count in first_item["monthly"].items():
            month_as_dt = datetime.datetime.strptime(month, "%Y-%m-%d")
            if month_as_dt > highest_month_as_dt:
                highest_month_as_dt = month_as_dt

        self.logger.info(f'Latest month in extract: {highest_month_as_dt.strftime("%Y-%m")}')
        return highest_month_as_dt.strftime("%Y-%m")

    def latest_month_exists_in_dynamodb(self, latest_date):
        response = self.aws_dynamodb_client.get_item(
            TableName=f"{self.dynamodb_table_prefix}Stats",
            Key={
                'TimePeriod': {'S': str(latest_date)}
            }
        )

        return True if 'Item' in response else False

    def update_dynamodb_totals(self, latest_month):
        previous_month = (datetime.datetime.strptime(latest_month, "%Y-%m") - relativedelta(months=1)).strftime("%Y-%m")
        self.logger.info(f'Previous month: {previous_month}')

        try:
            previous_month_response = self.aws_dynamodb_client.get_item(
                TableName=f"{self.dynamodb_table_prefix}Stats",
                Key={
                    'TimePeriod': {'S': previous_month}
                }
            )
        except Exception:
            self.logger.error('Error getting previous month row')
            return False

        try:
            totals_response = self.aws_dynamodb_client.get_item(
                TableName=f"{self.dynamodb_table_prefix}Stats",
                Key={
                    'TimePeriod': {'S': 'Total'}
                }
            )
        except Exception:
            self.logger.error('Error getting total row')
            return False

        try:
            previous_month_dict = previous_month_response['Item']
        except Exception:
            self.logger.error('No items found in previous month response object')
            return False

        try:
            totals_response_dict = totals_response['Item']
        except Exception:
            self.logger.error('No items found in total response object')
            return False

        for key, value in totals_response_dict.items():
            if key != 'TimePeriod':
                total_metric_count = int(list(value.items())[0][1])
                try:
                    last_month_metric_count = int(list(previous_month_dict[key].items())[0][1])
                except Exception:
                    self.logger.info(f"{key} not found in previous months metrics.. defaulting to 0")
                    last_month_metric_count = 0

                new_total_metric_count = total_metric_count + last_month_metric_count

                try:
                    self.aws_dynamodb_client.update_item(
                        TableName=f"{self.dynamodb_table_prefix}Stats",
                        Key={'TimePeriod': {'S': 'Total'}},
                        UpdateExpression=f"set {str(key)} = :count",
                        ExpressionAttributeValues={
                            ":count": {"N": str(new_total_metric_count)}
                        },
                        ReturnValues='ALL_NEW'
                    )
                    self.logger.info(f"Update total for {key} from {total_metric_count} to {new_total_metric_count} based on month: {previous_month}")
                except Exception as e:
                    self.logger.error(f'Error updating total for {key} from {total_metric_count} to {new_total_metric_count}')
                    self.logger.error(e)
                    return False

        return True

    def update_dynamodb_with_statistics(self, key, value):
        success = True
        for date, count_of_items in value['monthly'].items():
            try:
                date_formatted = datetime.datetime.strptime(date, "%Y-%m-%d").strftime("%Y-%m")
                self.aws_dynamodb_client.update_item(
                    TableName=f"{self.dynamodb_table_prefix}Stats",
                    Key={'TimePeriod': {'S': date_formatted}},
                    UpdateExpression = f'set {str(key)} = :count',
                    ExpressionAttributeValues={
                      ":count": {"N": str(count_of_items) if count_of_items is not None else "0"}
                    },
                    ReturnValues='ALL_NEW'
                )
            except Exception as e:
                success = False
                self.logger.error(e)

        return success

    def update_statistics(self):
        dict_of_statistics = self.get_statistics() if self.environment != 'local' else self.local_dev_response()

        if dict_of_statistics is None:
            self.logger.error("Issue with statistics so none returned")
            return False

        self.logger.info("=== Discovering if we are in new month ===")
        latest_month = self.get_latest_month(first_item=list(dict_of_statistics['statistics'].items())[0][1])

        if not self.latest_month_exists_in_dynamodb(latest_month):
            self.logger.info("Latest month from extract does not yet exist in dynamodb")
            self.update_totals = True

        self.logger.info("=== Starting dynamodb update ===")
        # looping over the individual metrics
        for key, value in dict_of_statistics['statistics'].items():
            key_edited = key.replace('404_', 'not_found_').replace('401_', 'unauthorised_').replace('403_', 'forbidden_')
            self.logger.info(f"Updating {key_edited}")
            success = self.update_dynamodb_with_statistics(key_edited, value)
            if not success:
                self.logger.error(f"{key_edited} has failed to update properly")
                return False

        if self.update_totals:
            self.logger.info("=== Updating dynamodb totals ===")
            success = self.update_dynamodb_totals(latest_month)
            if not success:
                self.logger.error(f"Total row has failed to update properly")
                return False

        self.logger.info("=== Update completed successfully ===")
        return True

def lambda_handler(event, context):

    work = StatisticsCollector()

    success = work.update_statistics()

    return {
        'message' : f"Success - {str(success)}"
    }
