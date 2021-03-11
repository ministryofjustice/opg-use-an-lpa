import gzip
import base64
import boto3
import os

def handler(event, context):
    encoded_zipped_data = event['awslogs']['data']
    zipped_data = base64.b64decode(encoded_zipped_data)
    log_message = gzip.decompress(zipped_data)
    sqs_send_message(str(log_message))

def sqs_send_message(log_message):
    queue_url = os.getenv('QUEUE_URL')
    client = boto3.client('sqs')
    response = client.send_message(
        QueueUrl=queue_url,
        MessageBody=log_message,
    )
    print(response)
