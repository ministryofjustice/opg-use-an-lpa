import os
import boto3
import logging
from merge_duplicate_identities import *

# Initialize the logger
logger = logging.getLogger()
logger.setLevel("INFO")

def lambda_handler(event, context):
    try:
        initialise()

        # Parse the input event
        execute = event['Execute']

        # Access environment variables
        bucket_name = os.environ.get('BUCKET')
        if not bucket_name:
            raise ValueError("Missing required environment variable BUCKET")

        environment_name = os.environ.get('ENVIRONMENT_NAME')
        if not environment_name:
            environment_name = ""

        work_file_prefix = os.environ.get('WORK_FILE_PREFIX')
        if not work_file_prefix:
            work_file_prefix = "todo"

        plan_file_prefix = os.environ.get('PLAN_FILE_PREFIX')
        if not plan_file_prefix:
            plan_file_prefix = "plan"


        if execute == 'True':
            execute_all_plans(bucket_name, plan_file_prefix, environment_name)


            data = {
                "statusCode": 200,
                "message": "Execution of all plans completed successfully",
            }

        else:
            build_plans(
                table_prefix=environment_name,
                bucket=bucket_name,
                work_prefix=work_file_prefix,
                plan_prefix=plan_file_prefix,
                limit=None,
                offset=0
            )

            data = {
                "statusCode": 200,
                "message": "Generation of all plans completed successfully"
            }

        return data

    except Exception as e:
        logger.error(f"Error processing merge plan lambda: {str(e)}")
        raise
