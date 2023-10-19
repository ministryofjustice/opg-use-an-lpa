locals {
  create_alarm = var.create_alarm && var.create_health_check
}

resource "aws_route53_record" "this" {
  zone_id = var.zone_id
  name    = "${var.dns_namespace_env}${var.dns_name}"
  type    = "A"

  alias {
    evaluate_target_health = false
    name                   = var.loadbalancer.dns_name
    zone_id                = var.loadbalancer.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }

  provider = aws.management
}

resource "aws_route53_health_check" "this" {
  count             = var.create_health_check ? 1 : 0
  fqdn              = aws_route53_record.this.fqdn
  reference_name    = "${substr(var.environment_name, 0, 20)}-${var.service_name}"
  port              = 443
  type              = "HTTPS"
  failure_threshold = 1
  request_interval  = 30
  measure_latency   = true
  regions           = ["us-east-1", "us-west-1", "us-west-2", "eu-west-1", "ap-southeast-1", "ap-southeast-2", "ap-northeast-1", "sa-east-1"]

  provider = aws.us-east-1
}

resource "aws_cloudwatch_metric_alarm" "this" {
  count               = local.create_alarm ? 1 : 0
  alarm_description   = "${var.environment_name} ${var.service_name} health check"
  alarm_name          = "${var.environment_name}-${var.service_name}-healthcheck-alarm"
  actions_enabled     = false
  comparison_operator = "LessThanThreshold"
  datapoints_to_alarm = 1
  evaluation_periods  = 1
  metric_name         = "HealthCheckStatus"
  namespace           = "AWS/Route53"
  period              = 60
  statistic           = "Minimum"
  threshold           = 1
  dimensions = {
    HealthCheckId = aws_route53_health_check.this[0].id
  }

  provider = aws.us-east-1
}
