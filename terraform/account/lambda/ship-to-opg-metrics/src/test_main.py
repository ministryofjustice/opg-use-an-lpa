import unittest
import os
import mock
from moto import mock_sqs
import requests
import requests_mock

OPG_METRICS_URL = "https://localhost"
API_KEY = "testapikeystring"

PAYLOAD = {
    "Records": [
        {
            "messageId": "dd121f41-054a-4380-afc4-ef72544aae8c",
            "receiptHandle": "AQEBU+bLgA1NJ1btTyzQ6qGhwD/bclqPzJIqqDoarCcW+T8XP6vASi6OqlmnB0bNCiv0gUHkCsfGoHLB8RYKvuCjUCehES93edeYddbr8rEMU5/MmEVnOqoW9mISZuO2mgA+U6JhkA9b5gvBNVStlOKBS/MZTRulb4IuTe9CEhqp+G1Mfl6MFp9hrFOFH3saJBwnFH1/j9/OtdgLgJY1hMEWsruEffrCAEZsgu6Y+Iitm+lvFFiSRAlHlpOVl/hgpytR8UvJaPCRCGf1085HEzdZlEOqrjtIqtkziVFa+MOfXruE21VAc1Yqh1XEgT7G/8zHa8ttF4rQ8pZtfT2ZMr1B50NtufmoPzsMZt9xEP8SkznQ00ZlU+GxIzfbx8ft7L8ISP9WEHUEP7LX21H0KRpXBZ2Fl4qU7FnQppEoq8e6D6o=",
            "body": "{'metrics': [{'metric': {'Project': 'useanlpadevelopment', 'MeasureName': 'DOWNLOAD_SUMMARY', 'MeasureValue': '1', 'Time': '1622720114831'}}]}",
            "attributes": {
                "ApproximateReceiveCount": "1",
                "SentTimestamp": "1622191541032",
                "SenderId": "AROAVLI4KOZPRZ4EKF65R:782uml1322-clsf-to-sqs",
                "ApproximateFirstReceiveTimestamp": "1622191631032"
            },
            "messageAttributes":
            {},
            "md5OfBody": "44157aeee09537c70ff3b3e24f257aaa",
            "eventSource": "aws:sqs",
            "eventSourceARN": "arn:aws:sqs:eu-west-1:367815980639:development-ship-to-opg-metrics",
            "awsRegion": "eu-west-1"
        }
    ]
}

@requests_mock.Mocker()
@mock.patch.dict(os.environ, {'OPG_METRICS_URL': OPG_METRICS_URL, 'API_KEY': API_KEY})
@mock_sqs
class TestLambdaFunction(unittest.TestCase):

    def test_handler(self, m):
        from main import handler

        m.register_uri('PUT', OPG_METRICS_URL + '/metrics',
                       json={'StatusCode': 200, 'Message': 'SUCCESS'})
        print(requests.put(OPG_METRICS_URL + '/metrics').json())

        event = PAYLOAD

        result = handler(event, {})
        self.assertEqual(result, {
          'metrics': [
            {
              'metric': 
              {
                'MeasureName': 'DOWNLOAD_SUMMARY',
                'MeasureValue': '1',
                'Project': 'useanlpadevelopment',
                'Time': '1622720114831'
              }
            }
          ]
        })
