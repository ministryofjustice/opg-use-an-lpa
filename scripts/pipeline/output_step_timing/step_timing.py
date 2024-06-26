import json
import argparse
import os
from datetime import datetime
import requests

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


def get_step_durations(data, job_name, step_names):
    """Check if the steps exist and return the step durations"""
    durations = []

    # Check if job name exists in the data
    jobs_found = search_for_jobs(data["jobs"], job_name)

    for job in jobs_found:
        # Check if the step name exists in the job
        steps_found = search_for_steps(job, step_names)

        for step in steps_found:
            # If the step exists, calculate its duration and append
            # the job, step and duration to the durations list
            duration = calculate_step_duration(step)

            # Shorten the name to display nicely in table without the bools
            shortened_job_name = shorten_job_name(job["name"])

            # Append the short job name, step and duration to the list, ready for
            # GitHub step summary
            durations.append((shortened_job_name, step["name"], duration))

    return durations


def search_for_jobs(data, job_name):
    """Search for the jobs in the data"""
    jobs_found = []
    for job in data:
        # Use startswith to allow us to search for the matrix jobs
        if job["name"] == job_name or job["name"].startswith(job_name):
            jobs_found.append(job)
    return jobs_found


def search_for_steps(job, step_names):
    """Search for the steps in the job"""
    steps_found = []
    for step in job["steps"]:
        if step["name"] in step_names:
            steps_found.append(step)
    return steps_found


def calculate_step_duration(step):
    start_time = datetime.strptime(step["started_at"], "%Y-%m-%dT%H:%M:%SZ")
    end_time = datetime.strptime(step["completed_at"], "%Y-%m-%dT%H:%M:%SZ")
    duration_seconds = (end_time - start_time).total_seconds()

    if duration_seconds == 0:
        return None
    elif duration_seconds < 60:
        duration = f"{duration_seconds:.0f}s"
    else:
        # Display the time in minutes:seconds where appropriate
        minutes, seconds = divmod(duration_seconds, 60)
        duration = f"{int(minutes)}:{int(seconds):02d}s"

    return duration


def main():

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

    # Extract the data and insert into lists
    for job_name, step_names in job_step_pairs.items():
        rows = get_step_durations(jobs_data, job_name, step_names)
        for short_job_name, step_name, duration in rows:
            if duration == None:
                continue
            job_column.append(short_job_name)
            step_column.append(step_name)
            duration_column.append(duration)

    # Write outputs to $GITHUB_OUTPUT
    if github_output:
        with open(github_output, "a") as output_file:
            output_file.write(f"jobs={json.dumps(job_column)}\n")
            output_file.write(f"steps={json.dumps(step_column)}\n")
            output_file.write(f"durations={json.dumps(duration_column)}\n")


if __name__ == "__main__":
    main()
