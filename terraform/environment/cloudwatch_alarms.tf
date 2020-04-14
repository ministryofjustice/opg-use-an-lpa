resource "aws_cloudwatch_metric_alarm" "viewer_5xx_errors" {
  actions_enabled = false
  # alarm_actions       = [aws_sns_topic.cloudwatch_topic.arn]
  alarm_description   = "Number of 5XX Errors returned to viewer users for ${local.environment}"
  alarm_name          = "${local.environment}-viewer-5XX-errors"
  comparison_operator = "GreaterThanThreshold"
  datapoints_to_alarm = 3
  dimensions = {
    "LoadBalancer" = trimprefix(split(":", aws_lb.viewer.arn)[5], "loadbalancer/")
  }
  evaluation_periods        = 3
  insufficient_data_actions = []
  metric_name               = "HTTPCode_Target_5XX_Count"
  namespace                 = "AWS/ApplicationELB"
  # ok_actions                = [aws_sns_topic.cloudwatch_topic.arn]
  period             = 60
  statistic          = "Sum"
  tags               = {}
  threshold          = 5
  treat_missing_data = "notBreaching"
}

resource "aws_cloudwatch_metric_alarm" "actor_5xx_errors" {
  actions_enabled = false
  # alarm_actions       = [aws_sns_topic.cloudwatch_topic.arn]
  alarm_description   = "Number of 5XX Errors returned to actor users for ${local.environment}"
  alarm_name          = "${local.environment}-actor-5XX-errors"
  comparison_operator = "GreaterThanThreshold"
  datapoints_to_alarm = 3
  dimensions = {
    "LoadBalancer" = trimprefix(split(":", aws_lb.actor.arn)[5], "loadbalancer/")
  }
  evaluation_periods        = 3
  insufficient_data_actions = []
  metric_name               = "HTTPCode_Target_5XX_Count"
  namespace                 = "AWS/ApplicationELB"
  # ok_actions                = [aws_sns_topic.cloudwatch_topic.arn]
  period             = 60
  statistic          = "Sum"
  tags               = {}
  threshold          = 5
  treat_missing_data = "notBreaching"
}
