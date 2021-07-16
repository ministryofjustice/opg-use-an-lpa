import os
import requests
import ast

def handler(event, context):
    for message in event['Records']:
        records = ast.literal_eval(message["body"])
        print(records)

        call_api_gateway(records)

    return records


def call_api_gateway(json_data):
    url = os.getenv('OPG_METRICS_URL')
    api_key = os.getenv('API_KEY')
    method = 'PUT'
    path = '/metrics'
    headers = {
        'Content-Type': 'application/json',
        'Content-Length': str(len(str(json_data))),
        'x-api-key': api_key
    }

    response = requests.request(
        method=method,
        url=url+path,
        json=json_data,
        headers=headers
    )
    print(response.json())
