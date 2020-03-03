#!/usr/bin/env bash

function get_ecr_scan_findings() {
  aws ecr describe-image-scan-findings --repository-name ${ECR_PATH}/${1:?} --image-id imageTag=$IMAGE_TAG --region eu-west-1 | jq -r '. | .imageScanStatus.description'
}
function wait_for_ecs_scan() {
  aws ecr wait image-scan-complete --repository-name ${ECR_PATH}/${1:?} --image-id imageTag=$IMAGE_TAG --region eu-west-1
}

function recursive_wait() {
  for image in "${images[@]}"
  do
    wait_for_ecs_scan $image
  done
}

function recursive_check() {
  for image in "${images[@]}"
  do
    set +x;
    FINDINGS=$(get_ecr_scan_findings $image)
    if [[ $FINDINGS != "The scan was completed successfully." ]]; then
      CHECK_STATUS=1
    fi
    echo  $image: $FINDINGS
  done

  if [[ $CHECK_STATUS > 0 ]]; then
    exit 1
  fi
}

function parse_args() {
  for arg in "$@"
  do
      case $arg in
          -t|--tag)
          IMAGE_TAG="$2"
          ;;
      esac
  done
}

declare -a images=("api_app"
                    "api_web"
                    "front_app"
                    "front_web"
                    "pdf"
                    )

ECR_PATH="use_an_lpa"
IMAGE_TAG="latest"
parse_args $@
recursive_wait
recursive_check
