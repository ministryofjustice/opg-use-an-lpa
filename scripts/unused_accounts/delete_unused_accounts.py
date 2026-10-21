import argparse
import csv
import json
import os
from pathlib import Path
from datetime import datetime

import boto3
from boto3.dynamodb.conditions import Key
from botocore.exceptions import ClientError


class UnusedAccountsProcessor:
    def __init__(self, environment="demo"):
        self.environment = environment
        self.environment_details = self.set_environment_details(environment)
        
        # Initialize AWS clients
        aws_iam_session = self.set_iam_role_session()
        self.dynamodb = boto3.resource(
            "dynamodb",
            region_name="eu-west-1",
            aws_access_key_id=aws_iam_session["Credentials"]["AccessKeyId"],
            aws_secret_access_key=aws_iam_session["Credentials"]["SecretAccessKey"],
            aws_session_token=aws_iam_session["Credentials"]["SessionToken"],
        )
        self.dynamodb_client = boto3.client(
            "dynamodb",
            region_name="eu-west-1",
            aws_access_key_id=aws_iam_session["Credentials"]["AccessKeyId"],
            aws_secret_access_key=aws_iam_session["Credentials"]["SecretAccessKey"],
            aws_session_token=aws_iam_session["Credentials"]["SessionToken"],
        )
        
        # Initialize DynamoDB tables
        self.actor_users_table = self.dynamodb.Table(
            f"{environment}-ActorUsers"
        )
        self.user_lpa_actor_map_table = self.dynamodb.Table(
            f"{environment}-UserLpaActorMap"
        )
        
        # State tracking file
        self.done_file = Path(f"results/done_users_{environment}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
        self.done_file.parent.mkdir(parents=True, exist_ok=True)
        self.done_users = self.load_done_users()
    
    @staticmethod
    def set_environment_details(environment):
        aws_account_ids = {
            "production": "690083044361",
            "preproduction": "888228022356",
            "development": "367815980639",
        }
        
        aws_account_id = aws_account_ids.get(environment, "367815980639")
        
        if environment in aws_account_ids.keys():
            account_name = environment
        else:
            account_name = "development"
        
        return {
            "name": environment.lower(),
            "account_name": account_name.lower(),
            "account_id": aws_account_id,
        }
    
    def set_iam_role_session(self):
        if self.environment_details["name"] == "production":
            role_arn = "arn:aws:iam::{}:role/data-access".format(
                self.environment_details["account_id"]
            )
        else:
            role_arn = "arn:aws:iam::{}:role/operator".format(
                self.environment_details["account_id"]
            )
        
        sts = boto3.client("sts", region_name="eu-west-1")
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName="deleting_unused_accounts",
            DurationSeconds=900,
        )
        return session
    
    def load_done_users(self):
        """Load the list of already processed users"""
        if self.done_file.exists():
            with open(self.done_file, "r") as f:
                return set(json.load(f))
        return set()
    
    def save_done_users(self):
        """Save the list of processed users to file"""
        with open(self.done_file, "w") as f:
            json.dump(list(self.done_users), f, indent=2)
    
    def get_user_lpas_count(self, user_id):
        """
        Query UserLpaActorMap for the given user.
        Returns the count of LPAs for this user.
        Uses query instead of scan for efficiency.
        """
        try:
            response = self.user_lpa_actor_map_table.query(
                IndexName="UserIndex",
                KeyConditionExpression=Key("UserId").eq(user_id)
            )
            return len(response.get("Items", []))
        except ClientError as e:
            print(f"Error querying LPAs for user {user_id}: {e}")
            return None
    
    def delete_actor_user(self, user_id):
        """
        Delete an actor user from the ActorUsers table.
        Uses a transaction to ensure atomicity.
        """
        try:
            self.dynamodb_client.transact_write_items(
                TransactItems=[
                    {
                        "Delete": {
                            "TableName": f"{self.environment}-ActorUsers",
                            "Key": {"Id": {"S": user_id}},
                        }
                    }
                ]
            )
            return True
        except ClientError as e:
            print(f"Error deleting user {user_id}: {e}")
            return False
    
    def process_unused_accounts(self, csv_file_path):
        """
        Read the CSV file from dynamodb_export.py and process each user.
        """
        if not Path(csv_file_path).exists():
            print(f"CSV file not found: {csv_file_path}")
            return
        
        processed_count = 0
        deleted_count = 0
        skipped_count = 0
        
        with open(csv_file_path, "r") as f:
            reader = csv.DictReader(f)
            
            for row in reader:
                user_id = row.get("UserId")
                email = row.get("Email")
                
                if not user_id:
                    print("Skipping row with missing UserId")
                    continue
                
                # Skip if already processed
                if user_id in self.done_users:
                    print(f"Already processed: {user_id} ({email})")
                    skipped_count += 1
                    continue
                
                print(f"\nProcessing user: {user_id} ({email})")
                processed_count += 1
                
                # Get current count of LPAs for this user
                lpa_count = self.get_user_lpas_count(user_id)
                
                if lpa_count is None:
                    print(f"  Error retrieving LPA count, skipping deletion")
                    self.done_users.add(user_id)
                    self.save_done_users()
                    continue
                
                if lpa_count > 0:
                    print(f"  User has {lpa_count} LPA(s), skipping deletion")
                    self.done_users.add(user_id)
                    self.save_done_users()
                    continue
                
                # Delete the user
                print(f"  Deleting user (LPA count: 0)")
                if self.delete_actor_user(user_id):
                    print(f"  ✓ User deleted successfully")
                    deleted_count += 1
                else:
                    print(f"  ✗ Failed to delete user")
                
                # Mark as done regardless of deletion success
                self.done_users.add(user_id)
                self.save_done_users()
        
        # Print summary
        print("\n" + "=" * 60)
        print("SUMMARY")
        print("=" * 60)
        print(f"Processed: {processed_count}")
        print(f"Deleted: {deleted_count}")
        print(f"Skipped (already had LPAs): {skipped_count}")
        print(f"Total users marked as done: {len(self.done_users)}")
        print(f"Done file saved to: {self.done_file}")
        print("=" * 60)


def parse_args():
    parser = argparse.ArgumentParser(
        description="Process unused accounts and delete those with no LPAs"
    )
    parser.add_argument(
        "--environment",
        default="demo",
        choices=["demo", "development", "preproduction", "production"],
        help="The environment to process (default: demo)",
    )
    parser.add_argument(
        "--csv-file",
        default="results/UnusedAccounts-2026-10-01-2026-10-31.csv",
        help="Path to the CSV file from dynamodb_export.py",
    )
    
    return parser.parse_args()


def main():
    args = parse_args()
    
    processor = UnusedAccountsProcessor(environment=args.environment)
    processor.process_unused_accounts(args.csv_file)


if __name__ == "__main__":
    main()
