resource "aws_cloudwatch_metric_alarm" "viewer_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to viewer users for ${local.environment_name}"
  alarm_name          = "${local.environment_name} viewer 5XX errors"
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
}

resource "aws_cloudwatch_metric_alarm" "actor_5xx_errors" {
  actions_enabled     = true
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "5XX Errors returned to actor users for ${local.environment_name}"
  alarm_name          = "${local.environment_name} actor 5XX errors"
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
}

locals {
  function = [
    "verifyAgainstLpa",
    "validateCode",
  ]
}

resource "aws_cloudwatch_log_metric_filter" "function_errors" {
  for_each       = toset(local.event_codes)
  name           = "${local.environment_name}_${lower(each.value)}_errors"
  pattern        = "{ $.level_name = \"ERROR\" && $.extra.function = \"${each.value}\" }"
  log_group_name = aws_cloudwatch_log_group.application_logs.name

  metric_transformation {
    name          = "${lower(each.value)}_event"
    namespace     = "${local.environment_name}_events"
    value         = "1"
    default_value = "0"
  }
}

resource "aws_cloudwatch_metric_alarm" "function_errors" {
  for_each            = toset(local.event_codes)
  actions_enabled     = true
  alarm_name          = each.value.metric_name
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "increase in errors for the function ${lower(each.value)}"
  namespace           = "application function errors"
  metric_name         = "${local.environment_name}_${lower(each.value)}_errors"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  period              = 180
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  statistic           = "Sum"
  threshold           = 5
  treat_missing_data  = "notBreaching"
}
