import json
import argparse
import os
import requests
from datetime import datetime

# Get environment variables
run_id = os.getenv("GITHUB_RUN_ID")
repo = os.getenv("GITHUB_REPOSITORY")
token = os.getenv("GITHUB_TOKEN")
github_output = os.getenv("GITHUB_OUTPUT")

# Set headers for GitHub API request
headers = {
    "Authorization": f"token {token}",
    "Accept": "application/vnd.github.v3+json",
}

# Get the workflow run details
run_url = f"https://api.github.com/repos/{repo}/actions/runs/{run_id}"
run_response = requests.get(run_url, headers=headers)
run_data = run_response.json()

# Get the list of jobs in the workflow run
jobs_url = run_data["jobs_url"]
jobs_response = requests.get(jobs_url, headers=headers)
jobs_data = jobs_response.json()


def shorten_job_name(job_name):
    """Shorten the job name to include only the first two elements in parentheses"""
    if "(" in job_name and ")" in job_name:
        prefix = job_name.split("(")[0].strip()
        parenthesis_content = job_name[job_name.find("(") + 1 : job_name.find(")")]
        elements = parenthesis_content.split(",")

        if len(elements) > 1:
            short_name = f"{prefix} ({elements[0].strip()}, {elements[1].strip()})"
        else:
            short_name = f"{prefix} ({elements[0].strip()})"
    else:
        short_name = job_name

    return short_name


def calculate_step_durations(data, job_name, step_names):
    """Check if the steps exist and return the step durations"""
    durations = []
    for job in data["jobs"]:
        if job["name"] == job_name or job["name"].startswith(job_name):
            for step in job["steps"]:
                if step["name"] in step_names:
                    start_time = datetime.strptime(
                        step["started_at"], "%Y-%m-%dT%H:%M:%SZ"
                    )
                    end_time = datetime.strptime(
                        step["completed_at"], "%Y-%m-%dT%H:%M:%SZ"
                    )
                    duration_seconds = (end_time - start_time).total_seconds()
                    if duration_seconds < 60:
                        duration = f"{duration_seconds:.0f}s"
                    else:
                        minutes, seconds = divmod(duration_seconds, 60)
                        duration = f"{int(minutes)}:{int(seconds):02d}s"

                    shortened_name = shorten_job_name(job["name"])

                    durations.append((shortened_name, step["name"], duration))
    return durations


# Set up argument parser
parser = argparse.ArgumentParser(
    description="Calculate the duration of specific job steps."
)
parser.add_argument(
    "-json", type=str, required=True, help="JSON string of job-step pairs"
)

# Parse arguments
args = parser.parse_args()

# Calculate the durations for the specified job and step
job_step_pairs = json.loads(args.json)

# Collect outputs
job_column = []
step_column = []
duration_column = []

for job_name, step_names in job_step_pairs.items():
    durations = calculate_step_durations(jobs_data, job_name, step_names)
    for full_job_name, step_name, duration in durations:
        job_column.append(full_job_name)
        step_column.append(step_name)
        duration_column.append(duration)

# Write outputs to $GITHUB_OUTPUT
if github_output:
    with open(github_output, "a") as output_file:
        output_file.write(f"jobs={json.dumps(job_column)}\n")
        output_file.write(f"steps={json.dumps(step_column)}\n")
        output_file.write(f"durations={json.dumps(duration_column)}\n")
