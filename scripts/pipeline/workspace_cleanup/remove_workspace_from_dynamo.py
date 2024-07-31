import argparse
import boto3
import boto3.session
import os

class RemoveWorkspaceProtection:
    def __init__(self) -> None:
        self.aws_account_id = "367815980639" #ual-dev-operator

        aws_iam_session = self.set_iam_role_session()

        self.aws_dynamodb_client = self.get_aws_client(
            'dynamodb',
            aws_iam_session,
        )

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/opg-use-an-lpa-ci'
        else:
            role_arn = f'arn:aws:iam::{self.aws_account_id}:role/operator'

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName=f"Manually_deleting_workspace_item_from_Dynamodb",
            DurationSeconds=900
        )

        return session
    
    @staticmethod
    def get_aws_client(client_type, aws_iam_session, region='eu-west-1'):
        client = boto3.client(
            client_type,
            region_name=region,
            aws_access_key_id=aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=aws_iam_session['Credentials']['SessionToken'])
        return client

    def delete_item_from_dynamodb(self, workspace_name: str) -> str:
        """ 
        Takes and input of a session and workspace name, and returns an item from 
        the Workspace Cleanup Dynamodb table
        """

        try:
            item = self.aws_dynamodb_client.delete_item(
                TableName='WorkspaceCleanup',
                Key = {
                    "WorkspaceName": {"S": f"{workspace_name}"}
                }
            )
            
        except Exception as e:
            print(f"Error deleting item from DynamoDB: {e}")

        return f"Workspace: {workspace_name}, has been removed from the DynamoDB table\n{item}"

def main():

    # Define args that will be passed into the scirpt 
    parser = argparse.ArgumentParser(
    description='Recieve Workspace Name')
    parser.add_argument('--workspace_name',
                        help='The name of the workspace item to remove from dynamodb',
                        required=True, 
                        type=str)

    args = parser.parse_args()

    delete_workspace = RemoveWorkspaceProtection()

    # Deletes an item from our WorkspaceCleanup dynamodb table, based on the workspace name
    result = delete_workspace.delete_item_from_dynamodb(args.workspace_name)
    print(result)

if __name__ == "__main__":
    main()
