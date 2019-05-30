import urllib.request
import boto3
import argparse
import json
import os
import pprint

parser = argparse.ArgumentParser(
    description='Open or close stack security groups.')
parser.add_argument("config_file_path", type=str,
                    help="Path to config file produced by terraform")
parser.add_argument('--open', dest='action_flag', action='store_const',
                    const=True, default=False,
                    help='open security group (default: close security group)')

args = parser.parse_args()
pp = pprint.PrettyPrinter(indent=4)


def get_ip_addresses():
    host_public_cidr = urllib.request.urlopen(
        'http://checkip.amazonaws.com').read().decode('utf8').rstrip() + "/32"
    return host_public_cidr


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


def allow_ci_ingress(account_id, ingress_cidr):
    workspace = os.getenv('TF_WORKSPACE')
    security_groups = ["-actor-loadbalancer", "-viewer-loadbalancer"]
    session = set_iam_role_session(account_id)
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

    for sg_name in security_groups:
        if args.action_flag:
            try:
                print("Adding SG rule to " + workspace + sg_name)
                response = ec2.authorize_security_group_ingress(
                    GroupName=workspace + sg_name,
                    IpPermissions=[
                        {
                            'FromPort': 443,
                            'IpProtocol': 'tcp',
                            'IpRanges': [
                                {
                                    'CidrIp': ingress_cidr,
                                    'Description': 'ci ingress'
                                },
                            ],
                            'ToPort': 443,
                        },
                    ],
                )
                sg = ec2.describe_security_groups(
                    GroupNames=[
                        workspace + sg_name,
                    ],
                )
                if 'ci ingress' in sg['SecurityGroups'][0]['IpPermissions'][0]['IpRanges'][-1]['Description']:
                    print("Added security group ingress rule " + str(sg['SecurityGroups'][0]['IpPermissions']
                                                                     [0]['IpRanges'][-1]))
            except:
                print("unable to open security group, possibly already open")
        if not args.action_flag:
            sg_rules = ec2.describe_security_groups(
                GroupNames=[
                    workspace + sg_name,
                ],
            )['SecurityGroups'][0]['IpPermissions'][0]['IpRanges']
            for i in sg_rules:
                if 'Description' in i and (i['Description']) == "ci ingress":
                    cidr_range_to_remove = i['CidrIp']
                    print("found security group ingress rule " + str(i))
                    try:
                        print("Removing security group ingress rule from " +
                              workspace + sg_name)
                        response = ec2.revoke_security_group_ingress(
                            GroupName=workspace + sg_name,
                            IpPermissions=[
                                {
                                    'FromPort': 443,
                                    'IpProtocol': 'tcp',
                                    'IpRanges': [
                                        {
                                            'CidrIp': cidr_range_to_remove,
                                            'Description': 'ci ingress'
                                        },
                                    ],
                                    'ToPort': 443,
                                },
                            ],
                        )
                        sg_rules = ec2.describe_security_groups(
                            GroupNames=[
                                workspace + sg_name,
                            ],
                        )['SecurityGroups'][0]['IpPermissions'][0]['IpRanges']
                        for i in sg_rules:
                            if 'Description' in i and (i['Description']) == "ci ingress":
                                print("unable to close security group" + str(i))
                                exit(1)
                    except:
                        print("unable to close security group")


account_id = read_parameters_from_file(args.config_file_path)['account_id']
ingress_cidr = get_ip_addresses()
allow_ci_ingress(account_id, ingress_cidr)
