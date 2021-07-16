import unittest
import os
import boto3
import mock
from moto import mock_sqs

METRIC_PROJECT_NAME = "useanlpadevelopment"
QUEUE_URL = "testqueueurl"
QUEUE_NAME = "test_queue"
AWS_REGION = 'eu-west-1'

PAYLOAD = {'awslogs': {'data': 'H4sIAAAAAAAAAFVS22rjMBD9laDXjRNdfJED+xCatCxsm9JkWZZ1MYokpwbZciU73RDy7x3bCXQNxjozc47PzOiMKu29OOjdqdFogVbL3TJ/XG+3y4c1miL7UWsHYRYnnEQpxzFLIWzs4cHZroFMwmlXGcIozUXTmFKKtrR1DhV+LNy2Tovqv8rZsdQf2gVAmHVeB6IOTCPmAOeFYixhes+YisIoZLwQsZR7iVUqFSn6n/tu76Urm/4/96VptfNo8feLfCCNL4LWBv7dBwpaMFao3HdVJdwJvQ6u1kddtz3vjEo1NEjSiPM4DeOQpiHlFDMchjihNOIJYSQGmGIS8zTlJExIEkYx4xz8tCWMsBUVTIPElOEkTXAETUxvowX5c3YDGVpkaNtJCbDojDlNDhpmLFqtJs+r+4mo1aRx2oM9iBTWTW4dTCjGGZpmSFrI/WtBCWR130gurRqVV5vfTz83y1W+/fX4uHz5cyUMWeBfABmgmB5G+IbyWlQj/2mz+3G3Hllvoq6HygwpXYjOtENcgde+5yFBMSUBjgOc7Ei0oBxUZ9A7TcJvGC+uhsGsE6PdojQjcdi2d3J+Z6vK1sNxq92xlHr+rIr+vcJZ89YMMqasey6hvW9phPeD0iiQwXMl9EegXz+3YK9QdLXsr83Ac/q9g8VByb2z1dcycCt1UKqh7MXa9jsJYrzXbE/iAK4A7FirkFOh0hAzWOWeRkWGLhd0eb18AinNXL1SAwAA'}}


@mock.patch.dict(os.environ, {'AWS_DEFAULT_REGION': AWS_REGION, 'METRIC_PROJECT_NAME': METRIC_PROJECT_NAME, 'QUEUE_URL': QUEUE_URL})
@mock_sqs
class TestLambdaFunction(unittest.TestCase):

    def test_handler(self):
        from main import handler

        sqs = boto3.client("sqs", region_name=AWS_REGION)

        dlq_url = sqs.create_queue(QueueName=QUEUE_URL)["QueueUrl"]
        dlq_arn = sqs.get_queue_attributes(QueueUrl=dlq_url)[
            "Attributes"]["QueueArn"]

        attributes = {
            "DelaySeconds": "900",
            "MaximumMessageSize": "262144",
            "MessageRetentionPeriod": "1209600",
            "ReceiveMessageWaitTimeSeconds": "20",
            "RedrivePolicy": '{"deadLetterTargetArn": "%s", "maxReceiveCount": 100}'
            % (dlq_arn),
            "VisibilityTimeout": "43200",
        }

        sqs.create_queue(QueueName=QUEUE_NAME, Attributes=attributes)

        event = PAYLOAD

        result = handler(event, {})
        self.assertEqual(result, {
            'metrics': [
                {
                    'metric':
                    {
                        'MeasureName': 'DOWNLOAD_SUMMARY',
                        'MeasureValue': '1.0',
                        'MeasureValueType': 'DOUBLE',
                        'Project': 'useanlpadevelopment',
                        'Time': '1623079705373'
                    }
                }
            ]
        })
