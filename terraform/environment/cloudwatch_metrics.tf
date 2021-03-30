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
    "OLDER_LPA_TOO_OLD",
    "SHARE_CODE_NOT_FOUND",
  ]
}

resource "aws_cloudwatch_log_metric_filter" "log_event_code_metrics" {
  for_each       = toset(local.event_codes)
  name           = "${local.environment}_${lower(each.value)}"
  pattern        = "{ $.context.event_code = \"${each.value}\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(each.value)}_event"
    namespace     = "${local.environment}_events"
    value         = "1"
    default_value = "0"
  }
}
