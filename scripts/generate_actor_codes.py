import urllib.request
import boto3
import json
import os
import sys

class CodeGeneration:
    aws_account_id = ''
    aws_iam_session = ''
    aws_ecs_client = ''
    aws_ecs_cluster = ''
    aws_ec2_client = ''
    aws_private_subnets = ''
    code_creation_security_group = ''
    environment = ''
    code_creation_task_definition = ''
    code_creation_task = ''
    nextForwardToken = ''
    logStreamName = ''

    def __init__(self, environment):
        self.environment=environment
        self.aws_ecs_cluster=os.environ.get('AWS_ECS_CLUSTER')

        self.set_iam_role_session()

        self.aws_ecs_client=boto3.client(
            'ecs',
            region_name='eu-west-1',
            aws_access_key_id=os.environ.get('AWS_ACCESS_KEY_ID'),
            aws_secret_access_key=os.environ.get('AWS_SECRET_ACCESS_KEY'),
            aws_session_token=os.environ.get('AWS_SESSION_TOKEN'))

        self.aws_ec2_client = boto3.client(
            'ec2',
            region_name='eu-west-1',
            aws_access_key_id=os.environ.get('AWS_ACCESS_KEY_ID'),
            aws_secret_access_key=os.environ.get('AWS_SECRET_ACCESS_KEY'),
            aws_session_token=os.environ.get('AWS_SESSION_TOKEN'))

        self.get_code_creation_task_definition()
        self.get_subnet_id()

        self.code_creation_security_group = self.get_security_group_id('{}-code-creation-ecs-service'.format(
            self.environment))

    def get_code_creation_task_definition(self):
      # get the latest task definition for seeding
      # returns task definition arn

        self.code_creation_task_definition = self.aws_ecs_client.list_task_definitions(
            familyPrefix='{}-code-creation'.format(self.environment),
            status='INACTIVE',
            sort='DESC',
            maxResults=1
        )['taskDefinitionArns'][0]
        print(self.code_creation_task_definition)

    def set_iam_role_session(self):
        self.aws_account_id=os.environ.get('AWS_ACCOUNT_ID')

        role_arn = 'arn:aws:iam::{}:role/operator'.format(
            self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='starting_code_creation_ecs_task',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_security_group_id(self, security_group_name):
      # get security group ids by security group name
      # returns security group id
        security_group_id = self.aws_ec2_client.describe_security_groups(
            Filters=[
                {
                    'Name': 'group-name',
                    'Values': [security_group_name + "*"]
                },
            ],
            MaxResults=50
        )['SecurityGroups'][0]['GroupId']
        return security_group_id

    def get_subnet_id(self):
      # get ids for private subnets
      # returns a list of private subnet ids
        subnets = self.aws_ec2_client.describe_subnets(
            Filters=[
                {
                    'Name': 'tag:Name',
                    'Values': [
                        'private',
                    ]
                },
            ],
            MaxResults=5
        )
        for subnet in subnets['Subnets']:
            self.aws_private_subnets.append(subnet['SubnetId'])

    def run_creation_task(self, lpauids):
      # run a code creation task in ecs

        print("starting creation task...")
        running_tasks = self.aws_ecs_client.run_task(
            cluster=self.aws_ecs_cluster,
            taskDefinition=self.code_creation_task_definition,
            launchType='FARGATE',
            overrides={
                'containerOverrides': [
                    {
                        'command': [
                            'php',
                            'console.php',
                            'actorcode:create',
                            lpauids
                        ]
                    }
                ]
            },
            networkConfiguration={
                'awsvpcConfiguration': {
                    'subnets': self.aws_private_subnets,
                    'securityGroups': [
                        self.code_creation_security_group,
                    ],
                    'assignPublicIp': 'DISABLED'
                }
            },
        )
        self.code_creation_task = running_tasks['tasks'][0]['taskArn']
        print(self.code_creation_task)

    def check_task_status(self):
      # returns the status of the generation task

        tasks = self.aws_ecs_client.describe_tasks(
            cluster=self.aws_ecs_cluster,
            tasks=[
                self.code_creation_task,
            ]
        )
        return tasks['tasks'][0]['lastStatus']

    def wait_for_task_to_start(self):
      # wait for the generation task to start

        print("waiting for generation task to start...")
        waiter = self.aws_ecs_client.get_waiter('tasks_running')
        waiter.wait(
            cluster=self.aws_ecs_cluster,
            tasks=[
                self.code_creation_task,
            ],
            WaiterConfig={
                'Delay': 10,
                'MaxAttempts': 100
            }
        )

    def get_logs(self):
      # retrieve logstreeam for the generation task started
      # formats and prints simple log output

        log_events = self.aws_logs_client.get_log_events(
            logGroupName='use-an-lpa',
            logStreamName=self.logStreamName,
            nextToken=self.nextForwardToken,
            startFromHead=False
        )
        for event in log_events['events']:
            print('timestamp: {0}: message: {1}'.format(
                event['timestamp'], event['message']))
        self.nextForwardToken = log_events['nextForwardToken']

    def print_task_logs(self):
      # lifecycle for getting log streams
      # get logs while task is running
      # after task finishes, print remaining logs

        self.logStreamName = 'code_creation_app.use-an-lpa/app/{}'.format(
            self.code_creation_task.rsplit('/', 1)[-1])
        print("Streaming logs for logstream: ".format(self.logStreamName))

        self.nextForwardToken = 'f/0'

        while self.check_task_status() == "RUNNING":
            self.get_logs()

        self.get_logs()
        print("task stopped running")


def main(argv):

    if len(argv) != 2:
        print('generate-actor-codes.py <environment> <comma separated lpa uids>')
        sys.exit()

    work = CodeGeneration(argv[0])
    work.run_creation_task(argv[1])
    work.wait_for_task_to_start()
    work.print_task_logs()

if __name__ == "__main__":
    main(sys.argv[1:])
