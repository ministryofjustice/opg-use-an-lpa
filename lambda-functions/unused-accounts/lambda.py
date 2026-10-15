import os
import logging
from delete_unused_accounts import *

# Initialize the logger
logger = logging.getLogger()
logger.setLevel("INFO")


def parse_execute(event):
    """Execute may be passed as a boolean or as the string "True"/"False"."""
    execute = event.get("Execute", False)
    if isinstance(execute, str):
        return execute.strip().lower() == "true"
    return bool(execute)


def lambda_handler(event, context):
    try:
        initialise()

        execute = parse_execute(event or {})

        # Access environment variables
        bucket_name = os.environ.get("BUCKET")
        if not bucket_name:
            raise ValueError("Missing required environment variable BUCKET")

        environment_name = os.environ.get("ENVIRONMENT_NAME") or ""
        work_file_prefix = os.environ.get("WORK_FILE_PREFIX") or "todo"
        plan_file_prefix = os.environ.get("PLAN_FILE_PREFIX") or "plan"
        months_inactive = int(os.environ.get("MONTHS_INACTIVE") or "6")

        if execute:
            summary = execute_all_deletion_plans(
                bucket=bucket_name,
                plan_prefix=plan_file_prefix,
                table_prefix=environment_name,
                months_inactive=months_inactive,
            )

            return {
                "statusCode": 200,
                "message": "Execution of all deletion plans completed successfully",
                "summary": summary,
            }

        summary = build_deletion_plans(
            table_prefix=environment_name,
            bucket=bucket_name,
            work_prefix=work_file_prefix,
            plan_prefix=plan_file_prefix,
            months_inactive=months_inactive,
        )

        return {
            "statusCode": 200,
            "message": "Generation of all deletion plans completed successfully",
            "summary": summary,
        }

    except Exception as e:
        logger.error(f"Error processing unused accounts lambda: {str(e)}")
        raise

