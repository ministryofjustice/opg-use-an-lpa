locals {
  event_codes = [
    "ACCOUNT_ACTIVATED",
    "ACCOUNT_CREATED",
    "ACCOUNT_DELETED",
    "DOWNLOAD_SUMMARY",
    "OLDER_LPA_DOES_NOT_MATCH",
    "OLDER_LPA_HAS_ACTIVATION_KEY",
    "OLDER_LPA_INVALID_STATUS",
    "OLDER_LPA_NOT_ELIGIBLE",
    "OLDER_LPA_NOT_FOUND",
    "OLDER_LPA_SUCCESS",
    "OLDER_LPA_CLEANSE_SUCCESS",
    "OLDER_LPA_TOO_OLD",
    "OLDER_LPA_ALREADY_ADDED",
    "OLDER_LPA_FORCE_ACTIVATION_KEY",
    "SHARE_CODE_NOT_FOUND",
    "VIEW_LPA_SHARE_CODE_NOT_FOUND",
    "VIEW_LPA_SHARE_CODE_SUCCESS",
    "VIEW_LPA_SHARE_CODE_EXPIRED",
    "VIEW_LPA_SHARE_CODE_CANCELLED",
    "ADD_LPA_FOUND",
    "ADD_LPA_NOT_FOUND",
    "ADD_LPA_NOT_ELIGIBLE",
    "ADD_LPA_ALREADY_ADDED",
    "ADD_LPA_SUCCESS",
    "ADD_LPA_FAILURE",
    "LPA_REMOVED",
    "OLDER_LPA_FOUND",
    "OOLPA_KEY_REQUESTED_FOR_DONOR",
    "OOLPA_KEY_REQUESTED_FOR_ATTORNEY",
    "OOLPA_PHONE_NUMBER_PROVIDED",
    "OOLPA_PHONE_NUMBER_NOT_PROVIDED",
    "OLDER_LPA_KEY_ALREADY_REQUESTED",
    "OLDER_LPA_NEEDS_CLEANSING",
    "UNEXPECTED_DATA_LPA_API_RESPONSE",
  ]
}

resource "aws_cloudwatch_log_metric_filter" "log_event_code_metrics" {
  for_each       = toset(local.event_codes)
  name           = "${local.environment_name}_${lower(each.value)}"
  pattern        = "{ $.context.event_code = \"${each.value}\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(each.value)}_event"
    namespace     = "${local.environment_name}_events"
    value         = "1"
    default_value = "0"
  }
}

locals {
  rate_limit_events = [
    "actor_login_failure",
    "viewer_code_failure",
    "actor_code_failure",
  ]
}

resource "aws_cloudwatch_log_metric_filter" "rate_limiting_metrics" {
  for_each       = toset(local.rate_limit_events)
  name           = "${local.environment_name}_${lower(each.value)}"
  pattern        = "{ $.context.code = \"429\" && $.context.message = \"${each.value}*\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(each.value)}_rate_limit_event"
    namespace     = "${local.environment_name}_events"
    value         = "1"
    default_value = "0"
  }
}

locals {
  login_attempt_status = [
    "403",
    "404",
    "401",
  ]
}

resource "aws_cloudwatch_log_metric_filter" "login_attempt_failures" {
  for_each       = toset(local.login_attempt_status)
  name           = "${local.environment_name}_${lower(each.value)}"
  pattern        = "{  $.message = \"Authentication failed for*\" && $.message = \"*with code ${each.value}\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(each.value)}_login_attempt_failures"
    namespace     = "${local.environment_name}_events"
    value         = "1"
    default_value = "0"
  }
}

# no change
