resource "aws_sns_topic" "cloudwatch_application_insights" {
  name              = "user-updates-topic"
  kms_master_key_id = "alias/pagerduty-sns"
  delivery_policy   = <<EOF
{
  "http": {
    "defaultHealthyRetryPolicy": {
      "minDelayTarget": 20,
      "maxDelayTarget": 20,
      "numRetries": 3,
      "numMaxDelayRetries": 0,
      "numNoDelayRetries": 0,
      "numMinDelayRetries": 0,
      "backoffFunction": "linear"
    },
    "disableSubscriptionOverrides": false,
    "defaultRequestPolicy": {
      "headerContentType": "text/plain; charset=UTF-8"
    }
  }
}
EOF
}

resource "aws_applicationinsights_application" "environment" {
  count                  = var.cloudwatch_application_insights_enabled ? 1 : 0
  resource_group_name    = aws_resourcegroups_group.environment.name
  auto_config_enabled    = true
  cwe_monitor_enabled    = true
  ops_center_enabled     = true
  ops_item_sns_topic_arn = aws_sns_topic.cloudwatch_application_insights.arn
  provider               = aws.region
}
