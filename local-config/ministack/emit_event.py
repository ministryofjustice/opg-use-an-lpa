import argparse
import json

from lambda_tools import client


def emit_event(event):
    entry = {
        "EventBusName": "default",
        "Source": event["source"],
        "DetailType": event["detail-type"],
        "Detail": json.dumps(event["detail"]),
    }
    response = client("events").put_events(Entries=[entry])
    print(json.dumps(response, indent=2))
    if response["FailedEntryCount"]:
        raise RuntimeError(f"EventBridge rejected event: {response['Entries']}")
    return response


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("payload", type=json.loads)
    args = parser.parse_args()
    emit_event(args.payload)
