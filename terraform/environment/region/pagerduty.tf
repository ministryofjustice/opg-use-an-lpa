data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}

resource "pagerduty_service_integration" "cloudwatch_integration" {
  name    = "${data.pagerduty_vendor.cloudwatch.name} ${var.environment_name}"
  service = var.pagerduty_service_id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "aws_sns_topic" "cloudwatch_to_pagerduty" {
  name              = "CloudWatch-to-PagerDuty-${var.environment_name}"
  kms_master_key_id = data.aws_kms_alias.pagerduty_sns.target_key_arn

  provider = aws.region
}

resource "aws_sns_topic_subscription" "cloudwatch_sns_subscription" {
  topic_arn              = aws_sns_topic.cloudwatch_to_pagerduty.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration.integration_key}/enqueue"

  provider = aws.region
}

data "aws_sns_topic" "cloudwatch_application_insights" {
  name     = "cloudwatch_application_insights"
  provider = aws.region
}

resource "pagerduty_service_integration" "cloudwatch_application_insights" {
  count   = var.cloudwatch_application_insights_enabled ? 1 : 0
  name    = "Use an LPA ${data.aws_region.current.name} Cloudwatch Application Insights Ops Item Alarm"
  service = var.pagerduty_service_id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "aws_sns_topic_subscription" "cloudwatch_application_insights" {
  count                  = var.cloudwatch_application_insights_enabled ? 1 : 0
  topic_arn              = data.aws_sns_topic.cloudwatch_application_insights.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_application_insights[0].integration_key}/enqueue"
  provider               = aws.region
}
