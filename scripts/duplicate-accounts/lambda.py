import od
import boto3
import logging
from merge_duplicate_identity import (*)

# Initialize the logger
logger = logging.getLogger()
logger.setLevel("INFO")

def lambda_handler(event, context):
    try:
        # Parse the input event
        execute = event['Execute']

        # Access environment variables
        bucket_name = os.environ.get('BUCKET')
        if not bucket_name:
            raise ValueError("Missing required environment variable BUCKET")

        environment_name = os.environ.get('ENVIRONMENT_NAME')
        if not bucket_name:
            raise ValueError("Missing required environment variable ENVIRONMENT_NAME")

        work_file_prefix = os.environ.get('WORK_FILE_PREFIX')
        if not bucket_name:
            raise ValueError("Missing required environment variable WORK_FILE_PREFIX")

        plan_file_prefix = os.environ.get('PLAN_FILE_PREFIX')
        if not bucket_name:
            raise ValueError("Missing required environment variable PLAN_FILE_PREFIX")


        if execute == 'True':
            execute_all_plans(bucket_name, plan_file_prefix, environment_name)

             return {
                 "statusCode": 200,
                 "message": "Execution of all plans completed successfully"
             }

        else:
            build_plans(
                table_prefix=environment_name,
                bucket=bucket_name,
                work_prefix=work_file_prefix,
                plan_prefix=plan_file_prefix,
                None,
                0
            )

            return {
                "statusCode": 200,
                "message": "Generation of all plans completed successfully"
            }

    except Exception as e:
        logger.error(f"Error processing merge plan lambda: {str(e)}")
        raise
