import boto3
import argparse
import json
import os
import pprint
# parser = argparse.ArgumentParser()
# parser.add_argument("config_file_path", type=str,
#                     help="Path to config file produced by terraform")

# args = parser.parse_args()
pp = pprint.PrettyPrinter(indent=4)


def read_parameters_from_file(config_file):
    with open(config_file) as json_file:
        parameters = json.load(json_file)
        return parameters


def set_iam_role_session(account_id):
    if os.getenv('CI'):
        role_arn = 'arn:aws:iam::{}:role/ci'.format(account_id)
    else:
        role_arn = 'arn:aws:iam::{}:role/account-write'.format(account_id)

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


def allow_ci_ingress():
    session = set_iam_role_session("367815980639")
    aws_access_key_id = \
        session['Credentials']['AccessKeyId']
    aws_secret_access_key = \
        session['Credentials']['SecretAccessKey']
    aws_session_token = \
        session['Credentials']['SessionToken']

    ec2 = boto3.client(
        'ec2',
        region_name='eu-west-1',
        aws_access_key_id=aws_access_key_id,
        aws_secret_access_key=aws_secret_access_key,
        aws_session_token=aws_session_token
    )

    workspace = os.getenv('TF_WORKSPACE')

    print(workspace)
    security_groups = ["-actor-loadbalancer", "-viewer-loadbalancer"]
    for sg_name in security_groups:
        print(sg_name)
        try:
            print("Adding sg rule")
            response = ec2.authorize_security_group_ingress(
                GroupName=workspace + sg_name,
                IpPermissions=[
                    {
                        'FromPort': 443,
                        'IpProtocol': 'tcp',
                        'IpRanges': [
                            {
                                'CidrIp': '0.0.0.0/0',
                                'Description': 'ci ingress'
                            },
                        ],
                        'ToPort': 443,
                    },
                ],
            )
            status = ec2.describe_security_groups(
                GroupNames=[
                    workspace + sg_name,
                ],
            )
            pp.pprint(status)

        except:
            response = ec2.revoke_security_group_ingress(
                GroupName=workspace + sg_name,
                IpPermissions=[
                    {
                        'FromPort': 443,
                        'IpProtocol': 'tcp',
                        'IpRanges': [
                            {
                                'CidrIp': '0.0.0.0/0',
                                'Description': 'ci ingress'
                            },
                        ],
                        'ToPort': 443,
                    },
                ],
            )

            status = ec2.describe_security_groups(
                GroupNames=[
                    workspace + sg_name,
                ],
            )
            pp.pprint(status)


# allow_ci_ingress(args.config_file_path)
allow_ci_ingress()
