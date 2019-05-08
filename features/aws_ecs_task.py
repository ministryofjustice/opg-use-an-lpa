import boto3
import argparse
import json
parser = argparse.ArgumentParser()
parser.add_argument("config_file_path", type=str,
                    help="Path to config file produced by terraform")

args = parser.parse_args()


def read_parameters_from_file(config_file):
    with open(config_file) as json_file:
        parameters = json.load(json_file)
        return parameters


def set_iam_role_session():
    sts = boto3.client(
        'sts',
        region_name='eu-west-1'
    )
    session = sts.assume_role(
        RoleArn='arn:aws:iam::367815980639:role/account-read',
        RoleSessionName='checking_ecs_task',
        DurationSeconds=900,
    )
    return session


def get_task_status(config_file):
    parameters = read_parameters_from_file(config_file)
    cluster = parameters['cluster_name']
    service = parameters['service_name']

    session = set_iam_role_session()
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
    task_arn = ecs.list_tasks(
        cluster=cluster,
        serviceName=service,
    )['taskArns'][0]

    try:
        print("Checking for task status")
        waiter = ecs.get_waiter('tasks_running')
        waiter.wait(
            cluster=cluster,
            tasks=[
                task_arn,
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
        task_status = ecs.describe_tasks(
            cluster=cluster,
            tasks=[
                task_arn,
            ],
        )['tasks'][0]['lastStatus']
        print(task_status)


get_task_status(args.config_file_path)
