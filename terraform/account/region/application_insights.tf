resource "aws_sns_topic" "cloudwatch_application_insights" {
  name              = "CloudWatch-Application-Insights-to-PagerDuty-${var.environment_name}"
  kms_master_key_id = aws_kms_alias.pagerduty_sns.target_key_arn

  provider = aws.region
}
