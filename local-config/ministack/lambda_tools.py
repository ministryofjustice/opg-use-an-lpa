import argparse
import base64
import json
import os
import sys

import boto3
from botocore.config import Config


REGION = "eu-west-1"
ENDPOINT = os.environ.get("AWS_ENDPOINT_URL", "http://localstack:4566")


def client(service):
    options = {}
    if service == "lambda":
        options["config"] = Config(read_timeout=960, retries={"max_attempts": 0})
    return boto3.client(service, region_name=REGION, endpoint_url=ENDPOINT, **options)


def invoke(name, payload):
    response = client("lambda").invoke(
        FunctionName=f"{name}-lambda",
        Payload=json.dumps(payload).encode(),
        LogType="Tail",
    )
    result = json.loads(response["Payload"].read())
    if response.get("LogResult"):
        print(base64.b64decode(response["LogResult"]).decode(), file=sys.stderr)
    print(json.dumps(result, indent=2))
    if response.get("FunctionError"):
        raise RuntimeError(f"Lambda invocation failed: {response['FunctionError']}")
    if isinstance(result, dict):
        if result.get("statusCode", 200) >= 400 or result.get("message") == "Success - False":
            raise RuntimeError("Lambda reported an unsuccessful result")
        summary = result.get("summary", {})
        if summary.get("failed", 0) or summary.get("files_failed", 0):
            raise RuntimeError("Lambda reported failed account operations")
    return result


def logs(name):
    log_client = client("logs")
    for page in log_client.get_paginator("filter_log_events").paginate(
        logGroupName=f"/aws/lambda/{name}-lambda",
    ):
        for event in page["events"]:
            print(event["message"])


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("action", choices=("invoke", "logs"))
    parser.add_argument("name", nargs="?", choices=(
        "event-receiver", "upload-statistics", "unused-accounts",
    ))
    parser.add_argument("payload", nargs="?", default="{}")
    args = parser.parse_args()
    if not args.name:
        parser.error(f"{args.action} requires a function name")
    if args.action == "invoke":
        invoke(args.name, json.loads(args.payload))
    else:
        logs(args.name)
