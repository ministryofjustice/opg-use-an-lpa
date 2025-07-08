resource "aws_applicationinsights_application" "environment" {
  count                  = var.cloudwatch_application_insights_enabled ? 1 : 0
  resource_group_name    = aws_resourcegroups_group.environment.name
  auto_config_enabled    = true
  cwe_monitor_enabled    = true
  ops_center_enabled     = true
  ops_item_sns_topic_arn = aws_sns_topic.cloudwatch_to_pagerduty.arn

  depends_on = [
    aws_ecs_cluster.use_an_lpa
  ]

  provider = aws.region
}
