import boto3
import time
import click

class DynamodbUpdate:
    wait_time = 0

    def __init__(self, dry_run):
        self.region = 'eu-west-1'
        self.dynamodb_resource = boto3.resource('dynamodb', region_name=self.region)
        self.user_list = self.get_user_emails()
        self.users_to_flag = []
        self.environment = 'demo'
        print(dry_run.lower())
        self.dry_run = False if dry_run.lower() == "false" else True
        print(self.dry_run)

    @staticmethod
    def get_user_emails():
        txt_file = open("user_emails.txt", "r")
        user_emails = txt_file.readlines()
        concat_user_emails = []
        for email in user_emails:
            concat_user_emails.append(email[0:14])
        txt_file.close()
        return concat_user_emails

    def update_users_to_flag(self, response):
        list_of_users = list(filter(lambda user: user['Email'][0:14] in self.user_list, response['Items']))

        for u in list_of_users:
            self.users_to_flag.append(u)

        print(f"length of array: {len(self.users_to_flag)}")

    def update_each_with_new_field(self, table):
        print("Beginning update of records")
        for user in self.users_to_flag:
            response = table.update_item(
                Key={
                    'Id': user["Id"]
                },
                UpdateExpression="SET NeedsReset = :needReset",
                ExpressionAttributeValues={':needReset': int(time.time())},
                ReturnValues="UPDATED_NEW"
            )
            if response["ResponseMetadata"]["HTTPStatusCode"] != 200:
                print(f"Failure updating: {user['Email']}")

    def update_users(self):
        table = self.dynamodb_resource.Table(f'{self.environment}-ActorUsers')
        full_start_time = time.time()
        response = table.scan()
        self.update_users_to_flag(response)

        count = 0
        while 'LastEvaluatedKey' in response:
            count += 1
            start_time = time.time()

            response = table.scan(ExclusiveStartKey=response['LastEvaluatedKey'])
            self.update_users_to_flag(response)
            print(f"Page: {count}, took  {round(time.time() - start_time, 2)} to run")

        print(f"Full scan finished in {round(time.time() - full_start_time, 2)}")
        print("===Users to Update===")

        update_users_csv = open("update_users.csv", "w")
        for u in self.users_to_flag:
            update_users_csv.write(str(f"{u['Id'],u['Email']}\n"))
        update_users_csv.close()

        print(f"Count of users to flag: {len(self.users_to_flag)}")

        if self.dry_run:
            print("Dry run -- no updates to process")
        else:
            print("Real run -- updating users")
            self.update_each_with_new_field(table)

    def list_updated_user_records(self):
        rng = len(self.users_to_flag) // 99 + 1
        query_keys = [[] for i in range(rng)]

        count = 0
        updated_users_csv = open("updated_users.csv", "w")
        for u in self.users_to_flag:
            count += 1
            query_keys[count // 99].append({"Id": u["Id"]})

        count_of_needs_reset = 0
        for items in query_keys:
            response = self.dynamodb_resource.batch_get_item(
                RequestItems={
                    f'{self.environment}-ActorUsers': {
                        'Keys': items,
                        'ConsistentRead': True
                    }
                },
                ReturnConsumedCapacity='TOTAL'
            )

            for item in response["Responses"][f"{self.environment}-ActorUsers"]:
                needs_reset = ''
                if item.get('NeedsReset'):
                    count_of_needs_reset += 1
                    needs_reset = item['NeedsReset']
                updated_users_csv.write(str(f"{item['Id']},{item['Email']},{needs_reset}\n"))

        print(f"Count of users successfully updated: {count_of_needs_reset}")
        updated_users_csv.close()

@click.command()
@click.option("-d", "--dry_run", default="true")
def main(dry_run):
    dynamodb_update = DynamodbUpdate(dry_run)
    dynamodb_update.update_users()
    dynamodb_update.list_updated_user_records()

"""
Updates email dynamodb records that match a particular set of email prefixes
"""
if __name__ == "__main__":
    main()
