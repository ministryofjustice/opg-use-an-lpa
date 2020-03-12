#!/usr/bin/env bash

function get_alb_rule_arn_view() {
  MM_VIEW_DNS_PREFIX="${ENVIRONMENT}."
  MM_VIEW_ALB_ARN=$(aws elbv2 describe-load-balancers --names  "${ENVIRONMENT}-viewer" | jq -r .[][]."LoadBalancerArn")
  MM_VIEW_LISTENER_ARN=$(aws elbv2 describe-listeners --load-balancer-arn ${MM_VIEW_ALB_ARN} | jq -r '.[][]  | select(.Protocol == "HTTPS") | .ListenerArn')
  MM_VIEW_RULE_ARN=$(aws elbv2 describe-rules --listener-arn ${MM_VIEW_LISTENER_ARN} | jq -r '.[][]  | select(.Priority == "1") | .RuleArn')
  if [ $ENVIRONMENT = "production" ]
  then
    MM_VIEW_DNS_PREFIX=""
  fi
}

function get_alb_rule_arn_use() {
  MM_USE_DNS_PREFIX="${ENVIRONMENT}."
  MM_USE_ALB_ARN=$(aws elbv2 describe-load-balancers --names  "${ENVIRONMENT}-actor" | jq -r .[][]."LoadBalancerArn")
  MM_USE_LISTENER_ARN=$(aws elbv2 describe-listeners --load-balancer-arn ${MM_USE_ALB_ARN} | jq -r '.[][]  | select(.Protocol == "HTTPS") | .ListenerArn')
  MM_USE_RULE_ARN=$(aws elbv2 describe-rules --listener-arn ${MM_USE_LISTENER_ARN} | jq -r '.[][]  | select(.Priority == "1") | .RuleArn')
  if [ $ENVIRONMENT = "production" ]
  then
    MM_USE_DNS_PREFIX=""
  fi
}

function enable_maintenance() {
  FRONT_END=${1:?}
  case $FRONT_END in
    use)
    MM_RULE_ARN=$MM_USE_RULE_ARN
    MM_DNS_PREFIX=$MM_USE_DNS_PREFIX
    SERVICE="actor"
    ;;
    view)
    MM_RULE_ARN=$MM_VIEW_RULE_ARN
    MM_DNS_PREFIX=$MM_VIEW_DNS_PREFIX
    SERVICE="viewer"
    echo "switched to view"
    ;;
  esac
  aws ssm put-parameter --name "${ENVIRONMENT}_${SERVICE}_enable_maintenance" --type "String" --value "true" --overwrite
  aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN \
  --conditions Field=host-header,Values="${MM_DNS_PREFIX}${FRONT_END}.lastingpowerofattorney.opg.service.justice.gov.uk"
}

function disable_maintenance() {
  FRONT_END=${1:?}
  case $FRONT_END in
    use)
    MM_RULE_ARN=$MM_USE_RULE_ARN
    SERVICE="actor"
    ;;
    view)
    MM_RULE_ARN=$MM_VIEW_RULE_ARN
    SERVICE="viewer"
    ;;
  esac
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
  get_alb_rule_arn_use
  if [ $MAINTENANCE_MODE = "True" ]
  then
    enable_maintenance use
  else
    disable_maintenance use
  fi
}
function start_view() {
  get_alb_rule_arn_view
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
