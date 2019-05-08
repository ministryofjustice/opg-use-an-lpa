import boto3
import argparse
parser = argparse.ArgumentParser()
parser.add_argument("cluster_arn", type=str,
                    help="ECS Cluster ARN")
parser.add_argument("service_name", type=str,
                    help="ECS Service Name")
args = parser.parse_args()


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


def get_task_status(cluster, service):
    aws_access_key = set_iam_role_session()

    aws_access_key_id = \
        aws_access_key['Credentials']['AccessKeyId']
    aws_secret_access_key = \
        aws_access_key['Credentials']['SecretAccessKey']
    aws_session_token = \
        aws_access_key['Credentials']['SessionToken']

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
        )
        print(task_status['tasks'][0]['lastStatus'])


# avei -- python3 aws_ecs_task.py 47-UML115dyna-use-an-lpa 47-UML115dyna-viewer
get_task_status(args.cluster_arn, args.service_name)
