import gzip
import base64
import boto3
import json
import os


def handler(event, context):
    print(event)
    cw_data = event['awslogs']['data']
    compressed_payload = base64.b64decode(cw_data)
    uncompressed_payload = gzip.decompress(compressed_payload)
    payload = json.loads(uncompressed_payload)
    metrics = {}
    metrics["metrics"] = []

    log_events = payload['logEvents']
    for log_event in log_events:
        print(f'LogEvent: {log_event}')
        metric = buildJsonString(json.loads(
            log_event["message"]), log_event["timestamp"])
        metrics["metrics"].append(metric)
        print(f'LogRecord: {metric}')

    print(f'LogRecords: {metrics}')
    sqs_send_message(str(metrics))

    return metrics


def buildJsonString(data, timestamp):
    resultData = {}
    resultData["Project"] = os.getenv('METRIC_PROJECT_NAME')
    resultData["Category"] = os.getenv('METRIC_CATEGORY')
    resultData["Subcategory"] = os.getenv('METRIC_SUBCATEGORY')
    resultData["Environment"] = os.getenv('METRIC_ENVIRONMENT')
    resultData["MeasureName"] = data["context"]["event_code"]
    resultData["MeasureValue"] = "1.0"
    resultData["MeasureValueType"] = "DOUBLE"
    resultData["Time"] = str(timestamp)

    metric = {}
    metric["metric"] = resultData
    return metric


def sqs_send_message(log_message):
    queue_url = os.getenv('QUEUE_URL')
    client = boto3.client('sqs')
    response = client.send_message(
        QueueUrl=queue_url,
        MessageBody=log_message,
    )
    print(response)
