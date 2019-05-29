import boto3
import argparse
import json
import os
import pprint

parser = argparse.ArgumentParser(
    description='Open or close stack security groups.')

parser.add_argument('--open', dest='action_flag', action='store_const',
                    const="open", default="close",
                    help='open security group (default: close security group)')
parser.add_argument('--ingress-cidr', dest='ingress_cidr',
                    help="ci ip address in cidr format")


args = parser.parse_args()
if args.action_flag == "open" and (args.ingress_cidr is None):
    parser.error("--open requires --ingress-cidr.")
print(args.action_flag)

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


def allow_ci_ingress(ingress_cidr):
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

    security_groups = ["-actor-loadbalancer", "-viewer-loadbalancer"]
    for sg_name in security_groups:
        if args.action_flag == "open":
            try:
                print("Adding SG rule to " + workspace + sg_name)
                # cidr_range = "255.155.255.255/32"
                cidr_range = ingress_cidr
                response = ec2.authorize_security_group_ingress(
                    GroupName=workspace + sg_name,
                    IpPermissions=[
                        {
                            'FromPort': 443,
                            'IpProtocol': 'tcp',
                            'IpRanges': [
                                {
                                    'CidrIp': cidr_range,
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
                if 'ci ingress' in status['SecurityGroups'][0]['IpPermissions'][0]['IpRanges'][-1]['Description']:
                    print("Added security group ingress rule " + str(status['SecurityGroups'][0]['IpPermissions']
                                                                     [0]['IpRanges'][-1]))
            except:
                print("unable to open security group, possibly already open")
        if args.action_flag == "close":
            sg = ec2.describe_security_groups(

                GroupNames=[
                    workspace + sg_name,
                ],
            )
            sg_rules = sg['SecurityGroups'][0]['IpPermissions'][0]['IpRanges']
            for i in sg_rules:
                if 'Description' in i and (i['Description']) == "ci ingress":
                    remove_cidr_range = i['CidrIp']
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
                                            'CidrIp': remove_cidr_range,
                                            'Description': 'ci ingress'
                                        },
                                    ],
                                    'ToPort': 443,
                                },
                            ],
                        )
                        for i in sg:
                            if 'Description' in i and (i['Description']) == "ci ingress":
                                print("unable to close security group")
                                exit(1)
                        print("ci ingress removed from security group " +
                              workspace + sg_name)
                    except:
                        print("unable to close security group")


# allow_ci_ingress(args.config_file_path)


allow_ci_ingress(args.ingress_cidr)
