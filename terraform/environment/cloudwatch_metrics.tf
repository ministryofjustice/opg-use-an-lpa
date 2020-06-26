locals {
  event_codes = [
    "ACCOUNT_ACTIVATED",
    "ACCOUNT_CREATED",
    "ACCOUNT_DELETED",
    "IDENTITY_HASH_CHANGE",
    "SHARE_CODE_NOT_FOUND",
  ]
}

resource "aws_cloudwatch_log_metric_filter" "log_metric" {
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
