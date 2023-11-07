resource "aws_cloudwatch_metric_alarm" "elasticache_high_cpu_utilization" {
  for_each                  = toset(aws_elasticache_replication_group.brute_force_cache_replication_group.member_clusters)
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "High CPU usage on ${lower(each.value)}"
  alarm_name                = "High CPU Utilization on ${lower(each.value)}"
  comparison_operator       = "GreaterThanThreshold"
  datapoints_to_alarm       = 2
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "CPUUtilization"
  namespace                 = "AWS/ElastiCache"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = "60"
  statistic                 = "Average"
  threshold                 = 90
  treat_missing_data        = "notBreaching"
  dimensions = {
    CacheClusterId = each.value
  }
}

resource "aws_cloudwatch_metric_alarm" "elasticache_high_swap_utilization" {
  for_each                  = toset(aws_elasticache_replication_group.brute_force_cache_replication_group.member_clusters)
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "High swap mem usage on ${lower(each.value)}"
  alarm_name                = "High swap mem Utilization on ${lower(each.value)}"
  comparison_operator       = "GreaterThanThreshold"
  datapoints_to_alarm       = 2
  evaluation_periods        = 2
  insufficient_data_actions = []
  metric_name               = "SwapUsage"
  namespace                 = "AWS/ElastiCache"
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  period                    = "60"
  statistic                 = "Sum"
  threshold                 = 50000000
  treat_missing_data        = "notBreaching"
  dimensions = {
    CacheClusterId = each.value
  }
}
