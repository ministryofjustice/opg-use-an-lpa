import urllib.request
import boto3
import argparse
import json
import os
import pprint


class andrew_aws:

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            parameters = json.load(json_file)
            return parameters['account_id']


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


def get_security_group(client, sg_name):
    return client.describe_security_groups(
        GroupNames=[
            sg_name,
        ],
    )


def remove_ci_ingress_rule_from_sg(client, sg_name, sg_rules):
    for sg_rule in sg_rules:
        if 'Description' in sg_rule and sg_rule['Description'] == "ci ingress":
            cidr_range_to_remove = sg_rule['CidrIp']
            print("found security group ingress rule " + str(sg_rule))
            try:
                print("Removing security group ingress rule from " +
                      sg_name)
                client.revoke_security_group_ingress(
                    GroupName=sg_name,
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

                # Verify sg rule has been removed
                # by looking up security group details again
                sg_rules = get_security_group(client, sg_name)[
                    'SecurityGroups'][0]['IpPermissions'][0]['IpRanges']

                for sg_rule in sg_rules:
                    if 'Description' in sg_rule and sg_rule[
                            'Description'] == "ci ingress":
                        print("unable to remove security group rule" + str(sg_rule))
                        exit(1)
            except:
                print("unable to remove security group rule")


def add_ci_ingress_rule_to_sg(client, sg_name, ingress_cidr):
    try:
        print("Adding SG rule to " + sg_name)
        client.authorize_security_group_ingress(
            GroupName=sg_name,
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
        sg = get_security_group(client, sg_name)

        if 'ci ingress' in sg['SecurityGroups'][0][
                'IpPermissions'][0]['IpRanges'][-1]['Description']:
            sg_rule = str(sg['SecurityGroups'][0]['IpPermissions']
                                              [0]['IpRanges'][-1])
            print("Added ingress rule {} to {}".format(
                sg_rule, sg_name))
    except:
        print("unable to open security group, possibly already open")


def modify_ci_ingress(account_id, ingress_cidr):
    workspace = os.getenv('TF_WORKSPACE')
    security_groups = [str(workspace) + "-actor-loadbalancer",
                       str(workspace) + "-viewer-loadbalancer"]
    session = set_iam_role_session(account_id)
    ec2 = boto3.client(
        'ec2',
        region_name='eu-west-1',
        aws_access_key_id=session['Credentials']['AccessKeyId'],
        aws_secret_access_key=session['Credentials']['SecretAccessKey'],
        aws_session_token=session['Credentials']['SessionToken']
    )

    for sg_name in security_groups:
        sg_rules = get_security_group(ec2, sg_name)[
            'SecurityGroups'][0]['IpPermissions'][0]['IpRanges']

        remove_ci_ingress_rule_from_sg(
            ec2, sg_name, sg_rules)
        if args.action_flag:
            add_ci_ingress_rule_to_sg(ec2, sg_name, ingress_cidr)


account_id = read_parameters_from_file(args.config_file_path)['account_id']
ingress_cidr = get_ip_addresses()
modify_ci_ingress(account_id, ingress_cidr)


def main():
    work = andrew_aws()
    account_id = work.read_parameters_from_file(args.config_file_path)
    ingress_cidr = get_ip_addresses()
    modify_ci_ingress(account_id, ingress_cidr)


if __name__ == "__main__":
    main()
