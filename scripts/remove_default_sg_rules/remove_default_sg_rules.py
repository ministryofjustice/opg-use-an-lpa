import urllib.request
import argparse
import boto3
import pprint
pp = pprint.PrettyPrinter(indent=4)


class SGIngressRulesRevoker:
    aws_account_id = ''
    aws_iam_session = ''
    iam_role_name = ''
    aws_ec2_client = ''
    regions = ''

    def __init__(self, account_id, iam_role_name):
        self.iam_role_name = iam_role_name
        self.aws_account_id = account_id
        self.list_all_regions()
        self.iterate_over_regions()

    def list_all_regions(self):
        home_region = 'eu-west-1'
        self.set_iam_role_session(home_region)
        self.get_ec2_client(home_region)
        self.regions = self.aws_ec2_client.describe_regions()['Regions']

    def iterate_over_regions(self):
        for region in self.regions:
            print(region['RegionName'])
            self.set_iam_role_session(region['RegionName'])
            self.get_ec2_client(region['RegionName'])
            ingress_permissions = self.get_ingress_permissions()

            if ingress_permissions == []:
                print("ingress rules not present")
            else:
                self.revoke_ingress(ingress_permissions)

    def get_ec2_client(self, region):
        self.aws_ec2_client = boto3.client(
            'ec2',
            region_name=region,
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def set_iam_role_session(self, region):
        role_arn = 'arn:aws:iam::{}:role/{}'.format(
            self.aws_account_id, self.iam_role_name)

        sts = boto3.client(
            'sts',
            region_name=region,
        )
        self.aws_iam_session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='revoking_default_sg_rules',
            DurationSeconds=900
        )

    def get_ingress_permissions(self):
        try:
            security_group = self.aws_ec2_client.describe_security_groups(
                Filters=[
                    {
                        'Name': 'group-name',
                        'Values': ['default']
                    },
                ],
                MaxResults=5
            )
            ingress_permissions = security_group['SecurityGroups'][0]['IpPermissions']
            pp.pprint(security_group['SecurityGroups'][0])
            return ingress_permissions
        except Exception as e:
            ingress_permissions = []
            print(e)

    def revoke_ingress(self, ingress_permissions):
        try:
            print("Removing security group ingress rule default security group")
            response = self.aws_ec2_client.revoke_security_group_ingress(
                GroupName='default',
                IpPermissions=ingress_permissions,
            )
            pp.pprint(response)
        except Exception as e:
            print(e)


def main():
    parser = argparse.ArgumentParser(
        description="Remove ingress rules from the default security group in each region.")
    parser.add_argument("--account_id",
                        help="AWS account ID to remove ingress rules in.")
    parser.add_argument("--role_name",
                        default="viewer",
                        help="AWS IAM role to use for procedure.")

    args = parser.parse_args()
    work = SGIngressRulesRevoker(args.account_id, args.role_name)


if __name__ == "__main__":
    main()
