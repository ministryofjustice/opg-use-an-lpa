resource "aws_cloudwatch_log_metric_filter" "account_creation" {
  name           = "account_creation_messages"
  pattern        = "{ $.message = \"Account with Id*created using email*\" }"
  log_group_name = data.aws_cloudwatch_log_group.use-an-lpa.name

  metric_transformation {
    name      = "EventCount"
    namespace = "account_creation"
    value     = "1"
  }
}
