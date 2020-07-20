#!/usr/bin/env bash


function get_pipeline_ids() {
  ${CURL_COMMAND} "https://circleci.com/api/v2/project/${PROJECT_SLUG}/pipeline?branch=${CIRCLE_BRANCH}" | jq  -r '.items[].id'
}

function get_workflow_ids() {
  pipeline_id=$1
  ${CURL_COMMAND} "https://circleci.com/api/v2/pipeline/${pipeline_id}/workflow" | jq -r '.items[].id'
}

function check_for_jobs_running_with_name_match() {
  workflow_id=$1
  job_name_match=$2
  ${CURL_COMMAND} "https://circleci.com/api/v2/workflow/${workflow_id}/job" | jq -e '.items[] | select((.name | contains("${job_name_match}")) and (.["status"] == "running")) | any // empty'
}

function get_workflow() {
  workflow_id=$1
  ${CURL_COMMAND} "https://circleci.com/api/v2/workflow/${workflow_id}"
}

function get_workflow_status() {
  workflow_id=$1
  ${CURL_COMMAND} "https://circleci.com/api/v2/workflow/${workflow_id}" | jq -r '.status'
}

function get_workflow_name() {
  workflow_id=$1
  ${CURL_COMMAND} "https://circleci.com/api/v2/workflow/${workflow_id}" | jq -r '.name'
}

function cancel_workflow() {
  workflow_id=$1
  ${CURL_COMMAND} "https://circleci.com/api/v2/workflow/${workflow_id}/cancel"
}

function protective_measures() {
  if [ ${CIRCLE_BRANCH} == "master" ]; then
    echo "this script should not be run on this branch: ${CIRCLE_BRANCH}"
    exit 1
  fi
}

protective_measures

JOB_TO_MATCH=terraform
CURL_COMMAND="curl -s -u ${CIRCLECI_API_KEY}:"
VCS_TYPE=github
PROJECT_SLUG=${VCS_TYPE}/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}

PIPELINE_IDS=$(get_pipeline_ids)
echo "Checking for workflows in branch ${CIRCLE_BRANCH} that can be cancelled..."
for PIPELINE_ID in ${PIPELINE_IDS}
  do
    echo "Getting IDs for running workflows on pipeline ${PIPELINE_ID}..."

    WORKFLOW_ID=$(get_workflow_ids ${PIPELINE_ID})
    WORKFLOW_STATUS=$(get_workflow_status ${WORKFLOW_ID})
    WORKFLOW_NAME=$(get_workflow_name ${WORKFLOW_ID})

    if [ "$WORKFLOW_NAME" == "pr_build" ] && [ "$WORKFLOW_STATUS" == "running" ]; then
      echo "Checking for running terraform jobs on workflow ${WORKFLOW_ID}"
      if $(check_for_jobs_running_with_name_match ${WORKFLOW_ID} terraform); then
          echo "terraform jobs are running, waiting instead"
      else
          echo "no terraform jobs are running, cancelling workflow"
          cancel_workflow ${WORKFLOW_ID}
      fi
    fi
  done
