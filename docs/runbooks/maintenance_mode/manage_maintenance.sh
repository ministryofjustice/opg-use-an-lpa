#!/usr/bin/env bash

function set_service_name() {
  local front_end=${1:?}
  case $front_end in
    use)
    SERVICE="actor"
    ;;
    view)
    SERVICE="viewer"
    ;;
  esac
}

function get_alb_rule_arn() {
  local front_end=${1:?}
  MM_ALB_ARN=$(aws elbv2 describe-load-balancers --names  "${ENVIRONMENT}-${SERVICE}" | jq -r .[][]."LoadBalancerArn")
  MM_LISTENER_ARN=$(aws elbv2 describe-listeners --load-balancer-arn ${MM_ALB_ARN} | jq -r '.[][]  | select(.Protocol == "HTTPS") | .ListenerArn')
  MM_RULE_ARN=$(aws elbv2 describe-rules --listener-arn ${MM_LISTENER_ARN} | jq -r '.[][]  | select(.Priority == "1") | .RuleArn')
  if [ $ENVIRONMENT = "production" ]
  then
    MM_USE_DNS_PREFIX=""
  fi
}

function enable_maintenance() {
  local front_end=${1:?}
  aws ssm put-parameter --name "${ENVIRONMENT}_${SERVICE}_enable_maintenance" --type "String" --value "true" --overwrite
  aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN \
  --conditions Field=host-header,Values="${MM_DNS_PREFIX}${front_end}.lastingpowerofattorney.opg.service.justice.gov.uk"
}

function disable_maintenance() {
  local front_end=${1:?}
  aws ssm put-parameter --name "${ENVIRONMENT}_${SERVICE}_enable_maintenance" --type "String" --value "false" --overwrite
  aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN \
  --conditions Field=path-pattern,Values='/maintenance'
}

function parse_args() {
  for arg in "$@"
  do
      case $arg in
          -e|--environment)
          ENVIRONMENT=$(echo "$2" | tr '[:upper:]' '[:lower:]')
          shift
          shift
          ;;
          -f|--front_end)
          FRONT_TO_SET=$(echo "$2" | tr '[:upper:]' '[:lower:]')
          shift
          shift
          ;;
          -m|--maintenance_mode)
          MAINTENANCE_MODE=True
          shift
          ;;
          -d|--disable_maintenance_mode)
          MAINTENANCE_MODE=False
          shift
          ;;
      esac
  done
}

function start_use() {
  set_service_name use
  get_alb_rule_arn use
  if [ $MAINTENANCE_MODE = "True" ]
  then
    enable_maintenance use
  else
    disable_maintenance use
  fi
}
function start_view() {
  set_service_name view
  get_alb_rule_arn view
  if [ $MAINTENANCE_MODE = "True" ]
  then
    enable_maintenance view
  else
    disable_maintenance view
  fi
}

MAINTENANCE_MODE=False
FRONT_TO_SET=both
parse_args $@

case $FRONT_TO_SET in
    use)
    start_use
    ;;
    view)
    start_view
    ;;
    *)
    start_use
    start_view
    ;;
esac
