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
    "OLDER_LPA_NEEDS_CLEANSING"
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
