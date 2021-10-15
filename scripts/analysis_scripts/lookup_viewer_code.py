import argparse
import json
import boto3


class AccountLookup:
    aws_account_id = ''
    aws_iam_session = ''
    aws_dynamodb_client = ''
    environment = ''
    output_json = {}

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
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def get_viewer_code(self, viewer_code):
        response = self.aws_dynamodb_client.query(
            TableName='{}-ViewerCodes'.format(self.environment),
            KeyConditionExpression='ViewerCode = :viewer_code',
            ExpressionAttributeValues={
                ':viewer_code': {'S': viewer_code}
            }
        )
        return response['Items'][0]

    def get_user_lpa_actor_from_actor_map(self, user_lpa_actor):
        response = self.aws_dynamodb_client.query(
            TableName='{}-UserLpaActorMap'.format(self.environment),
            KeyConditionExpression='Id = :user_lpa_actor',
            ExpressionAttributeValues={
                ':user_lpa_actor': {'S': user_lpa_actor}
            }
        )
        return response['Items'][0]

    def get_user(self, user_id):
        response = self.aws_dynamodb_client.query(
            TableName='{}-ActorUsers'.format(self.environment),
            KeyConditionExpression='Id = :user_id',
            ExpressionAttributeValues={
                ':user_id': {'S': user_id}
            }
        )

        return response['Items'][0]

    def get_viewer_code_data(self, viewer_code):
        viewer_code_data = {}
        try:
            viewer_code_data = self.get_viewer_code(viewer_code)
        except IndexError:
            viewer_code_data = {
                'ViewerCode': {
                    'S': "{} Not Found".format(viewer_code)
                },
                'SiriusUid': {
                    'S': ""
                },
                'Added': {
                    'S': ""
                },
                'Expires': {
                    'S': ""
                },
                'UserLpaActor': {
                    'S': "Not Found"
                }
            }

        try:
            user_lpa_actor = self.get_user_lpa_actor_from_actor_map(
                viewer_code_data['UserLpaActor']['S'])
            user = self.get_user(user_lpa_actor['UserId']['S'])
            user_email = user['Email']['S']
        except IndexError:
            user_email = "Not Found"

        self.output_json = {
            "email": user_email,
            "viewer_code": {
                "code": viewer_code_data['ViewerCode']['S'],
                "lpa_id": viewer_code_data['SiriusUid']['S'],
                "added": viewer_code_data['Added']['S'],
                "expires": viewer_code_data['Expires']['S'],
            }
        }

    def print_plaintext(self):
        print(
            "Viewer Code: {}".format(
                self.output_json['viewer_code']['code']),
            "\n\tEmail: {}".format(self.output_json['email']),
            "\n\tLPA Id: {}".format(
                self.output_json['viewer_code']['lpa_id']),
            "\n\tAdded: {}".format(
                self.output_json['viewer_code']['added']),
            "\n\tExpires: {}".format(
                self.output_json['viewer_code']['expires']),
            "\n"
        )


def main():
    arguments = argparse.ArgumentParser(
        description="Look up details of a Viewer Code.")
    arguments.add_argument("--environment",
                           default="production",
                           help="The environment to target. Defaults to production")

    arguments.add_argument("--viewer_code",
                           default="",
                           help="Viewer Code to look up")

    arguments.add_argument('--json', dest='output_json', action='store_const',
                           const=True, default=False,
                           help='Output json data instead of plaintext to terminal')

    args = arguments.parse_args()
    work = AccountLookup(args.environment)

    work.get_viewer_code_data(args.viewer_code)

    if args.output_json:
        print(json.dumps(work.output_json))
    else:
        work.print_plaintext()


if __name__ == "__main__":
    main()
