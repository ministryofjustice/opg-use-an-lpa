resource "aws_cloudwatch_metric_alarm" "health_check_alarm" {
  alarm_description   = "${local.environment} environment health check "
  alarm_name          = "${local.environment}-environment-healthcheck-alarm"
  comparison_operator = "LessThanThreshold"
  datapoints_to_alarm = 1
  evaluation_periods  = 1
  metric_name         = "HealthCheckStatus"
  namespace           = "AWS/Route53"
  period              = 60
  statistic           = "Minimum"
  threshold           = 1
  treat_missing_data  = "missing"
  tags                = local.default_tags

  dimensions = {
    HealthCheckId = aws_route53_health_check.health_check.id
  }
}

resource "aws_route53_health_check" "health_check" {
  fqdn              = aws_route53_record.viewer-use-my-lpa.fqdn
  reference_name    = "${local.environment}-health-check"
  resource_path     = "/healthcheck"
  port              = 443
  type              = "HTTPS_STR_MATCH"
  failure_threshold = 1
  request_interval  = 30
  measure_latency   = true
  search_string     = "{\"healthy\":true"
  regions           = ["us-east-1", "us-west-1", "us-west-2", "eu-west-1", "ap-southeast-1", "ap-southeast-2", "ap-northeast-1", "sa-east-1"]
  tags = merge(local.default_tags,
    { "Name" = "${local.environment}-health-check" },
  )
}
