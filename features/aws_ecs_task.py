import boto3
import argparse
import json
import os
parser = argparse.ArgumentParser()
parser.add_argument("config_file_path", type=str,
                    help="Path to config file produced by terraform")

args = parser.parse_args()


def read_parameters_from_file(config_file):
    with open(config_file) as json_file:
        parameters = json.load(json_file)
        return parameters


def set_iam_role_session(account_id):
    if os.getenv('CI'):
        role_arn = 'arn:aws:iam::{}:role/ci'.format(account_id)
    else:
        role_arn = 'arn:aws:iam::{}:role/account-read'.format(account_id)

    sts = boto3.client(
        'sts',
        region_name='eu-west-1',
    )
    session = sts.assume_role(
        RoleArn=role_arn,
        RoleSessionName='checking_ecs_task',
        DurationSeconds=900
    )
    return session


def get_task_status(config_file):  #
    # cluster = "74-waitforser-use-an-lpa"
    # account_id = "367815980639"
    parameters = read_parameters_from_file(config_file)
    cluster = parameters['cluster_name']
    # service = parameters['service_name']
    account_id = parameters['account_id']

    session = set_iam_role_session(account_id)
    aws_access_key_id = \
        session['Credentials']['AccessKeyId']
    aws_secret_access_key = \
        session['Credentials']['SecretAccessKey']
    aws_session_token = \
        session['Credentials']['SessionToken']

    ecs = boto3.client(
        'ecs',
        region_name='eu-west-1',
        aws_access_key_id=aws_access_key_id,
        aws_secret_access_key=aws_secret_access_key,
        aws_session_token=aws_session_token
    )
    try:
        print("Checking for services to settle...")
        waiter = ecs.get_waiter('services_stable')
        waiter.wait(
            cluster=cluster,
            services=[
                'api', 'actor', 'viewer',
            ],
            WaiterConfig={
                'Delay': 6,
                'MaxAttempts': 99
            }
        )
    except:
        print("Exceeded attempts checking for task status")
        exit(1)
    else:
        print("ECS services stable")


# get_task_status()
get_task_status(args.config_file_path)
