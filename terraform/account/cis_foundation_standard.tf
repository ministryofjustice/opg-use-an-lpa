
resource "aws_sns_topic" "cis_aws_foundations_standard" {
  name              = "cis_aws_foundations_standard"
  kms_master_key_id = "alias/aws/sns"
}
resource "aws_sns_topic_subscription" "cis_aws_foundations_standard" {
  topic_arn              = aws_sns_topic.cis_aws_foundations_standard.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration.integration_key}/enqueue"
}

data "aws_cloudwatch_log_group" "cloudtrail" {
  name = "opg_use_an_lpa_cloudtrail_${local.account_name}"
}

resource "aws_cloudwatch_log_metric_filter" "root_account_usage" {
  name           = "CIS-1.1-RootAccountUsage"
  pattern        = "{$.userIdentity.type=\"Root\" && $.userIdentity.invokedBy NOT EXISTS && $.eventType !=\"AwsServiceEvent\"}"
  log_group_name = data.aws_cloudwatch_log_group.cloudtrail.name
  metric_transformation {
    name      = "EventCount"
    namespace = "CISLogMetrics"
    value     = "1"
  }
}

resource "aws_cloudwatch_metric_alarm" "root_account_usage" {
  actions_enabled     = true
  alarm_name          = "CIS-1.1-RootAccountUsage"
  alarm_actions       = [aws_sns_topic.cis_aws_foundations_standard.arn]
  ok_actions          = [aws_sns_topic.cis_aws_foundations_standard.arn]
  alarm_description   = "root login usage count"
  namespace           = "CISLogMetrics"
  metric_name         = "EventCount"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  period              = 60
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  statistic           = "Sum"
  tags                = local.default_tags
  threshold           = 1
  treat_missing_data  = "notBreaching"
}
