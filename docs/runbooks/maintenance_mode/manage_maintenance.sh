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
  MM_ALB_ARN=$(aws elbv2 describe-load-balancers --names  "${ENVIRONMENT}-${SERVICE}" | jq -r .[][]."LoadBalancerArn")
  MM_LISTENER_ARN=$(aws elbv2 describe-listeners --load-balancer-arn ${MM_ALB_ARN} | jq -r '.[][]  | select(.Protocol == "HTTPS") | .ListenerArn')
  MM_RULE_ARN=$(aws elbv2 describe-rules --listener-arn ${MM_LISTENER_ARN} | jq -r '.[][]  | select(.Priority == "101") | .RuleArn')
  MM_RULE_ARN_WELSH=$(aws elbv2 describe-rules --listener-arn ${MM_LISTENER_ARN} | jq -r '.[][]  | select(.Priority == "100") | .RuleArn')

}

function enable_maintenance() {
  local front_end=${1:?}
  MM_DNS_PREFIX="${ENVIRONMENT}."
  if [ $ENVIRONMENT = "production" ]
  then
    MM_DNS_PREFIX=""
  fi
  aws ssm put-parameter --name "${ENVIRONMENT}_${SERVICE}_enable_maintenance" --type "String" --value "true" --overwrite
  aws-vault exec ual-dev-operator -- aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN_WELSH \
  --conditions Field=host-header,Values="${MM_DNS_PREFIX}${front_end}-lasting-power-of-attorney.service.gov.uk" Field=path-pattern,Values='/cy*'

  aws-vault exec ual-dev-operator --  aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN \
  --conditions Field=host-header,Values="${MM_DNS_PREFIX}${front_end}-lasting-power-of-attorney.service.gov.uk"

}

function disable_maintenance() {
  aws ssm put-parameter --name "${ENVIRONMENT}_${SERVICE}_enable_maintenance" --type "String" --value "false" --overwrite
  aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN \
  --conditions Field=path-pattern,Values='/maintenance'

  aws elbv2 modify-rule \
  --rule-arn $MM_RULE_ARN_WELSH \
  --conditions Field=path-pattern,Values='/maintenance-cy'
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

function start() {
  local front_end=${1:?}
  set_service_name $front_end
  get_alb_rule_arn
  if [ $MAINTENANCE_MODE = "True" ]
  then
    enable_maintenance $front_end
  else
    disable_maintenance
  fi
}

MAINTENANCE_MODE=False
FRONT_TO_SET=both
parse_args $@

case $FRONT_TO_SET in
    use)
    start use
    ;;
    view)
    start view
    ;;
    *)
    start use
    start view
    ;;
esac
