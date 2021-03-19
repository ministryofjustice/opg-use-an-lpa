import argparse
import boto3
import pandas as pd

class AccountLookup:
    aws_account_id = ''
    aws_iam_session = ''
    aws_dynamodb_client = ''
    environment = ''

    def __init__(self, environment):
        aws_account_ids = {
            'production': "690083044361",
            'preproduction': "888228022356",
            'development': "367815980639",
        }
        self.environment = environment

        self.aws_account_id = aws_account_ids.get(
            self.environment, "367815980639")

        self.set_iam_role_session()

        self.aws_dynamodb_client = boto3.client(
            'dynamodb',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def set_iam_role_session(self):
        if self.environment == "production":
            role_arn = 'arn:aws:iam::{}:role/read-only-db'.format(
                self.aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{}:role/operator'.format(
                self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='getting_service_statistics',
            DurationSeconds=3600
        )
        self.aws_iam_session = session

    def get_lpa_actor_maps(self):
        paginator = self.aws_dynamodb_client.get_paginator("scan")
        for page in paginator.paginate(
                TableName='{}-UserLpaActorMap'.format(self.environment),
                ):
            yield from page["Items"]

    def count_by_maps(self):
        print("counting lpas per account...")
        list_of_userids = []
        indicator = 0
        lpa_actor_maps = self.get_lpa_actor_maps()

        for lpa_map in lpa_actor_maps:
            list_of_userids.append(lpa_map['UserId']['S'])
            indicator += 1
            if indicator % 10000 == 0:
                print("Maps processed: ", indicator)

        actor_users_map_ids_series = pd.Series(list_of_userids)
        lpas_per_account_series = actor_users_map_ids_series.value_counts()
        print(lpas_per_account_series.value_counts())

def main():
    arguments = argparse.ArgumentParser(
        description="Look up an account by email address.")
    arguments.add_argument("--environment",
                        default="production",
                        help="The environment to target. Defaults to production")

    args = arguments.parse_args()
    work = AccountLookup(args.environment)
    work.count_by_maps()


if __name__ == "__main__":
    main()
