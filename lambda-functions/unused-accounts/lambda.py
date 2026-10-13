import os
import boto3
import logging
from delete_unused_accounts import *

# Initialize the logger
logger = logging.getLogger()
logger.setLevel("INFO")

def lambda_handler(event, context):
    try:
        initialise()

        # Parse the input event
        execute = event.get('Execute', 'False')
        if isinstance(execute, bool):
            execute = 'True' if execute else 'False'

        # Access environment variables
        bucket_name = os.environ.get('BUCKET')
        if not bucket_name:
            raise ValueError("Missing required environment variable BUCKET")

        environment_name = os.environ.get('ENVIRONMENT_NAME')
        if not environment_name:
            environment_name = ""

        plan_file_prefix = os.environ.get('PLAN_FILE_PREFIX')
        if not plan_file_prefix:
            plan_file_prefix = "plan"

        months_inactive = int(os.environ.get('MONTHS_INACTIVE', '6'))

        if execute == 'True':
            execute_all_deletion_plans(bucket_name, plan_file_prefix, environment_name)

            data = {
                "statusCode": 200,
                "message": "Execution of all deletion plans completed successfully",
            }

        else:
            build_deletion_plans(
                table_prefix=environment_name,
                bucket=bucket_name,
                plan_prefix=plan_file_prefix,
                months_inactive=months_inactive
            )

            data = {
                "statusCode": 200,
                "message": "Generation of all deletion plans completed successfully"
            }

        return data

    except Exception as e:
        logger.error(f"Error processing unused accounts lambda: {str(e)}")
        raise
