import unittest
from moto import mock_aws
import boto3
import datetime
from upload_statistics import StatisticsCollector

expected_values = {
    "2022-07": 5,
    "2022-08": 5,
    "2022-09": 5,
    "2022-10": 5,
}


class TestStatisticsCollector(unittest.TestCase):
    @mock_aws
    def setUp(self):
        """Setup the test environment. This will create the DynamoDB table and populate it with some data. It will also create the CloudWatch metrics."""
        boto3.setup_default_session()
        dynamo_client = boto3.client("dynamodb", region_name="eu-west-1")

        dynamo_client.create_table(
            TableName="test_Stats",
            AttributeDefinitions=[
                {"AttributeName": "TimePeriod", "AttributeType": "S"}
            ],
            KeySchema=[{"AttributeName": "TimePeriod", "KeyType": "HASH"}],
            ProvisionedThroughput={"ReadCapacityUnits": 5, "WriteCapacityUnits": 5},
        )

        dynamo_client.put_item(
            TableName="test_Stats",
            Item={
                "TimePeriod": {"S": "Total"},
            },
        )
        dynamo_client.put_item(
            TableName="test_Stats",
            Item={
                "TimePeriod": {"S": "2020-09"},
            },
        )


        cloudwatch_client = boto3.client("cloudwatch", region_name="eu-west-1")

        cloudwatch_client.put_metric_data(
            Namespace="test_events",
            MetricData=[
                {
                    "MetricName": "account_created_event",
                    "Value": 5,
                    "Timestamp": "2022-07-01",
                },
                {
                    "MetricName": "account_created_event",
                    "Value": 5,
                    "Timestamp": "2022-08-01",
                },
                {
                    "MetricName": "account_created_event",
                    "Value": 5,
                    "Timestamp": "2022-09-01",
                },
                {
                    "MetricName": "account_created_event",
                    "Value": 5,
                    "Timestamp": "2022-10-01",
                },
            ],
        )


        statistics_collector = StatisticsCollector()
        statistics_collector.aws_dynamodb_client = dynamo_client
        statistics_collector.aws_cloudwatch_client = cloudwatch_client
        statistics_collector.dynamodb_table_prefix = "test_"
        statistics_collector.region = "eu-west-1"
        statistics_collector.environment = "test"

        return statistics_collector
    
    @mock_aws
    def test_update_statistics(self):
        """Test that the statistics are updated correctly"""

        statistics_collector = self.setUp()
        statistics_collector.environment = "local"
        dynamo_client = statistics_collector.aws_dynamodb_client
        statistics_collector.update_statistics()

        for key, _ in expected_values.items():
            response = dynamo_client.get_item(
                TableName="test_Stats", Key={"TimePeriod": {"S": key}}
            )
            self.assertEqual(response["Item"]["TimePeriod"]["S"], key)

    def test_get_formatted_key(self):
        """Test that keys are formatted correctly"""
        test_keys = {
            "add_lpa_404_event": "add_lpa_not_found_event",
            "401_login_attempt_failures": "unauthorised_login_attempt_failures",
            "403_login_attempt_failures": "forbidden_login_attempt_failures",
        }
        statistics_collector = self.setUp()
        for key, value in test_keys.items():
            formatted_key = statistics_collector.get_formatted_key(key)
            self.assertEqual(formatted_key, value)

    @mock_aws
    def test_update_dynamodb_with_statistics(self):
        """Update the DynamoDB table with the statistics"""
        statistics_collector = self.setUp()
        stats = {
            "monthly": {
                "2022-07-01": 5,
                "2022-08-01": 5,
                "2022-09-01": 5,
                "2022-10-01": 5,
            }
        }

        self.assertEqual(
            statistics_collector.update_dynamodb_with_statistics(
                "account_created_event", stats
            ),
            True,
        )

    @mock_aws
    def test_update_dynamodb_totals(self):
        """Test that the totals are updated correctly"""
        latest_month = "2020-10"
        statistics_collector = self.setUp()

        statistics_collector.update_dynamodb_totals(latest_month)

        self.assertEqual(
            statistics_collector.update_dynamodb_totals(latest_month), True
        )

    @mock_aws
    def test_latest_month_exists_in_dynamodb(self):
        """Test that the latest month exists in the DynamoDB table"""
        statistics_collector = self.setUp()
        latest_month = "2020-09"

        self.assertEqual(
            statistics_collector.latest_month_exists_in_dynamodb(latest_month), True
        )

    def test_get_latest_month(self):
        """Test that the latest month is returned"""
        statistics_collector = self.setUp()
        stats = {
            "monthly": {
                "2022-07-01": 5,
                "2022-08-01": 5,
                "2022-09-01": 5,
                "2022-10-01": 5,
            }
        }

        self.assertEqual(statistics_collector.get_latest_month(stats), "2022-10")

    @mock_aws
    def test_get_cloudwatch_client(self):
        """Test that a Cloudwatch client is returned"""
        statistics_collector = self.setUp()
        statistics_collector.environment = "test"

        self.assertIsNotNone(statistics_collector.get_cloudwatch_client())

    @mock_aws
    def test_get_dynamodb_client(self):
        """Test that a DynamoDB client is returned"""
        statistics_collector = self.setUp()
        statistics_collector.environment = "test"

        self.assertIsNotNone(statistics_collector.get_dynamodb_client())

    @mock_aws
    def test_get_metric_statistics(self):
        """Test that the account_created_event metric is returned with a sum of 5"""
        statistics_collector = self.setUp()

        start_date = datetime.datetime.strptime("2022-07-01", "%Y-%m-%d")
        end_date = datetime.datetime.strptime("2022-08-01", "%Y-%m-%d")

        self.assertEqual(
            statistics_collector.get_metric_statistic(start_date, end_date, "account_created_event")[0]['Sum'],
            5,
        )
        

