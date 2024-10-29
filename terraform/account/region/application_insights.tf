resource "aws_sns_topic" "cloudwatch_application_insights" {
  name              = "CloudWatch-Application-Insights-to-PagerDuty-${var.environment_name}"
  kms_master_key_id = aws_kms_alias.pagerduty_sns.target_key_arn

  provider = aws.region
}

resource "aws_applicationinsights_application" "environment" {
  count                  = var.account.cloudwatch_application_insights_enabled ? 1 : 0
  resource_group_name    = aws_resourcegroups_group.environment.name
  auto_config_enabled    = true
  cwe_monitor_enabled    = true
  ops_center_enabled     = true
  ops_item_sns_topic_arn = aws_sns_topic.cloudwatch_application_insights.arn
  provider               = aws.region
}
