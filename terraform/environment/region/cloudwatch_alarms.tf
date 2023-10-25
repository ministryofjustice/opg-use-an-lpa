resource "aws_cloudwatch_metric_alarm" "viewer_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to viewer users for ${var.environment_name}"
  alarm_name          = "${var.environment_name} viewer 5XX errors"
  comparison_operator = "GreaterThanThreshold"
  datapoints_to_alarm = 2
  dimensions = {
    "LoadBalancer" = trimprefix(split(":", aws_lb.viewer.arn)[5], "loadbalancer/")
  }
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "HTTPCode_Target_5XX_Count"
  namespace                 = "AWS/ApplicationELB"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = 60
  statistic                 = "Sum"
  tags                      = {}
  threshold                 = 2
  treat_missing_data        = "notBreaching"

  provider = aws.region
}

resource "aws_cloudwatch_metric_alarm" "actor_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to actor users for ${var.environment_name}"
  alarm_name          = "${var.environment_name} actor 5XX errors"
  comparison_operator = "GreaterThanThreshold"
  datapoints_to_alarm = 2
  dimensions = {
    "LoadBalancer" = trimprefix(split(":", aws_lb.actor.arn)[5], "loadbalancer/")
  }
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "HTTPCode_Target_5XX_Count"
  namespace                 = "AWS/ApplicationELB"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = 60
  statistic                 = "Sum"
  tags                      = {}
  threshold                 = 2
  treat_missing_data        = "notBreaching"

  provider = aws.region
}

resource "aws_cloudwatch_metric_alarm" "unexpected_data_lpa_api_resposnes" {
  actions_enabled     = true
  alarm_name          = "${var.environment_name}_unexpected_data_lpa_api_resposnes"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "increase in unexpected data lpa api resposnes"
  namespace           = "${var.environment_name}_events"
  metric_name         = "${var.environment_name}_unexpected_data_lpa_api_responses"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  period              = 180
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  statistic           = "Sum"
  threshold           = 5
  treat_missing_data  = "notBreaching"

  provider = aws.region
}

resource "aws_cloudwatch_metric_alarm" "api_5xx_errors" {
  actions_enabled     = true
  alarm_name          = "${var.environment_name}_api_5xx_errors"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  metric_name         = aws_cloudwatch_log_metric_filter.api_5xx_errors.metric_transformation[0].name
  comparison_operator = "GreaterThanOrEqualToThreshold"
  period              = 60
  evaluation_periods  = 3
  datapoints_to_alarm = 2
  statistic           = "Sum"
  threshold           = 5
  namespace           = aws_cloudwatch_log_metric_filter.api_5xx_errors.metric_transformation[0].namespace
  treat_missing_data  = "notBreaching"
}

resource "aws_cloudwatch_metric_alarm" "actor_ddos_attack_external" {
  alarm_name          = "${var.environment_name}_ActorDDoSDetected"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "3"
  metric_name         = "DDoSDetected"
  namespace           = "AWS/DDoSProtection"
  period              = "60"
  statistic           = "Average"
  threshold           = "0"
  alarm_description   = "Triggers when AWS Shield Advanced detects a DDoS attack"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  dimensions = {
    ResourceArn = aws_lb.actor.arn
  }

  provider = aws.region
}

resource "aws_cloudwatch_metric_alarm" "viewer_ddos_attack_external" {
  alarm_name          = "${var.environment_name}_ViewerDDoSDetected"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "3"
  metric_name         = "DDoSDetected"
  namespace           = "AWS/DDoSProtection"
  period              = "60"
  statistic           = "Average"
  threshold           = "0"
  alarm_description   = "Triggers when AWS Shield Advanced detects a DDoS attack"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  dimensions = {
    ResourceArn = aws_lb.viewer.arn
  }
}

resource "aws_cloudwatch_metric_alarm" "admin_ddos_attack_external" {
  alarm_name          = "${var.environment_name}_AdminDDoSDetected"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "3"
  metric_name         = "DDoSDetected"
  namespace           = "AWS/DDoSProtection"
  period              = "60"
  statistic           = "Average"
  threshold           = "0"
  alarm_description   = "Triggers when AWS Shield Advanced detects a DDoS attack"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  dimensions = {
    ResourceArn = aws_lb.admin.arn
  }

  provider = aws.region
}

moved {
  from = aws_cloudwatch_metric_alarm.admin_ddos_attack_external[0]
  to   = aws_cloudwatch_metric_alarm.admin_ddos_attack_external
}
