import boto3
from boto3.dynamodb.types import TypeDeserializer
import argparse

def get_emails(table_name):
    session = boto3.Session()
    client = session.client("dynamodb")

    paginator = client.get_paginator('scan')

    response_iterator = paginator.paginate(
        TableName=table_name
    )

    data = []
    for page in response_iterator:
        for item in page['Items']:
            print(item['Email']['S'])



def main():
    parser = argparse.ArgumentParser(description="Get list of user emails.")
    parser.add_argument(
        "--table",
        default="demo-ActorUsers",
        help="The table containing the email addresses",
    )
    args = parser.parse_args()
    table = args.table
    get_emails(table)

if __name__ == "__main__":
    main()
