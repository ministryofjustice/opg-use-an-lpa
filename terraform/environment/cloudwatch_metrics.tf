resource "aws_cloudwatch_log_metric_filter" "account_creation" {
  name           = "${local.environment}_account_creation_messages"
  pattern        = "{ $.message = \"Account with Id*created using email*\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "AccountCreateEventCount"
    namespace     = "${local.environment}_account_creation"
    value         = "1"
    default_value = "0"
  }
}
