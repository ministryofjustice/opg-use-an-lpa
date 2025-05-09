locals {
  event_codes = [
    "event_code.ACCOUNT_ACTIVATED",
    "event_code.ACCOUNT_CREATED",
    "event_code.ACCOUNT_DELETED",
    "event_code.DOWNLOAD_SUMMARY",
    "event_code.OLDER_LPA_DOES_NOT_MATCH",
    "event_code.OLDER_LPA_HAS_ACTIVATION_KEY",
    "event_code.OLDER_LPA_INVALID_STATUS",
    "event_code.OLDER_LPA_NOT_ELIGIBLE",
    "event_code.OLDER_LPA_NOT_FOUND",
    "event_code.OLDER_LPA_SUCCESS",
    "event_code.OLDER_LPA_TOO_OLD",
    "event_code.OLDER_LPA_ALREADY_ADDED",
    "event_code.OLDER_LPA_FORCE_ACTIVATION_KEY",
    "event_code.OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED",
    "event_code.OLDER_LPA_PARTIAL_MATCH_TOO_RECENT",
    "event_code.SHARE_CODE_NOT_FOUND",
    "event_code.VIEW_LPA_SHARE_CODE_NOT_FOUND",
    "event_code.VIEW_LPA_SHARE_CODE_SUCCESS",
    "event_code.VIEW_LPA_SHARE_CODE_EXPIRED",
    "event_code.VIEW_LPA_SHARE_CODE_CANCELLED",
    "event_code.ADD_LPA_FOUND",
    "event_code.ADD_LPA_NOT_FOUND",
    "event_code.ADD_LPA_NOT_ELIGIBLE",
    "event_code.ADD_LPA_ALREADY_ADDED",
    "event_code.ADD_LPA_SUCCESS",
    "event_code.ADD_LPA_FAILURE",
    "event_code.LPA_REMOVED",
    "event_code.OLDER_LPA_FOUND",
    "role.OOLPA_KEY_REQUESTED_FOR_DONOR",
    "role.OOLPA_KEY_REQUESTED_FOR_ATTORNEY",
    "phone.OOLPA_PHONE_NUMBER_PROVIDED",
    "phone.OOLPA_PHONE_NUMBER_NOT_PROVIDED",
    "event_code.OLDER_LPA_KEY_ALREADY_REQUESTED",
    "event_code.OLDER_LPA_NEEDS_CLEANSING",
    "event_code.UNEXPECTED_DATA_LPA_API_RESPONSE",
    "key_status.ACTIVATION_KEY_EXISTS",
    "key_status.ACTIVATION_KEY_NOT_EXISTS",
    "key_status.ACTIVATION_KEY_EXPIRED",
    "event_code.IDENTITY_HASH_CHANGE",
    "event_code.USER_ABROAD_ADDRESS_REQUEST_SUCCESS",
    "event_code.ADDED_LPA_TYPE_HW",
    "event_code.ADDED_LPA_TYPE_PFA",
    "event_code.FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW",
    "event_code.FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA",
    "event_code.PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW",
    "event_code.PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA",
    "event_code.ACTIVATION_KEY_REQUEST_REPLACEMENT_ATTORNEY",
    "event_code.AUTH_ONELOGIN_ACCOUNT_MIGRATED",
    "event_code.AUTH_ONELOGIN_ACCOUNT_CREATED",
    "event_code.AUTH_ONELOGIN_ERROR",
    "event_code.AUTH_ONELOGIN_NOT_AVAILABLE",
    "event_code.AUTH_ONELOGIN_MISSING_SESSION",
    "event_code.AUTH_ONELOGIN_ACCOUNT_RECOVERED"
  ]
}

resource "aws_cloudwatch_log_metric_filter" "log_event_code_metrics" {
  for_each       = toset(local.event_codes)
  name           = "${var.environment_name}_${lower(split(".", each.value)[1])}"
  pattern        = "{ $.context.${split(".", each.value)[0]} = \"${split(".", each.value)[1]}\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(split(".", each.value)[1])}_event"
    namespace     = "${var.environment_name}_events"
    value         = "1"
    default_value = "0"
    unit          = "Count"
  }

  provider = aws.region
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
  name           = "${var.environment_name}_${lower(each.value)}"
  pattern        = "{ $.context.code = \"429\" && $.context.message = \"${each.value}*\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(each.value)}_rate_limit_event"
    namespace     = "${var.environment_name}_events"
    value         = "1"
    default_value = "0"
    unit          = "Count"
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
  name           = "${var.environment_name}_${lower(each.value)}"
  pattern        = "{  $.message = \"Authentication failed for*\" && $.message = \"*with code ${each.value}\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(each.value)}_login_attempt_failures"
    namespace     = "${var.environment_name}_events"
    value         = "1"
    default_value = "0"
    unit          = "Count"
  }

  provider = aws.region
}

resource "aws_cloudwatch_log_metric_filter" "api_5xx_errors" {
  name           = "${var.environment_name}_api_5xx_errors"
  pattern        = "{($.service_name = \"api\") && ($.status = 5*)}"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "api_5xx_errors"
    namespace     = "${var.environment_name}_events"
    value         = "1"
    default_value = "0"
  }

  provider = aws.region
}

resource "aws_cloudwatch_log_metric_filter" "onelogin_authentication_success" {
  count          = var.create_onelogin_dashboard ? 1 : 0
  name           = "${var.environment_name}_onelogin_authentication_success"
  pattern        = "{ $.message = \"Authentication successful for account with Id*\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "onelogin_authentication_success"
    namespace     = "${var.environment_name}_onelogin_events"
    value         = "1"
    default_value = "0"
  }

  provider = aws.region
}

resource "aws_cloudwatch_log_metric_filter" "login_attempt_success" {
  name           = "${var.environment_name}_login_attempt_success"
  pattern        = "{ ($.message = \"PATCH /v1/auth HTTP/1.1\" || $.request = \"PATCH /v1/auth HTTP/1.1\") && $.status = \"200\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "login_attempt_success"
    namespace     = "${var.environment_name}_events"
    value         = "1"
    default_value = "0"
  }

  provider = aws.region
}

resource "aws_cloudwatch_log_metric_filter" "application_error_count" {
  name           = "${var.environment_name}_application_error_count"
  pattern        = "{ $.service_name = \"front\" && ($.status != 2* && $.status != 3* && $.status != 404) }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "application_error_count"
    namespace     = "${var.environment_name}_events"
    value         = "1"
    default_value = "0"
  }

  provider = aws.region
}
