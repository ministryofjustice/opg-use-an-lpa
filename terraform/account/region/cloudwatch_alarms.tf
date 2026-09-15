locals {
  # HACK: Without this, we would need to do a targetted apply on the replication group before the alarms can be created
  brute_force_cache_replication_group_members = [for i in range(0, aws_elasticache_replication_group.brute_force_cache_replication_group.num_cache_clusters) : "${aws_elasticache_replication_group.brute_force_cache_replication_group.replication_group_id}-00${i + 1}"]
}

resource "aws_cloudwatch_metric_alarm" "elasticache_high_cpu_utilization" {
  for_each                  = toset(local.brute_force_cache_replication_group_members)
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

  provider = aws.region
}

resource "aws_cloudwatch_metric_alarm" "elasticache_memory_pressure" {
  for_each                  = toset(local.brute_force_cache_replication_group_members)
  actions_enabled           = true
  alarm_actions             = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description         = "Low freeable memory or high swap usage on ${lower(each.value)}"
  alarm_name                = "Memory pressure on ${lower(each.value)}"
  comparison_operator       = "GreaterThanThreshold"
  datapoints_to_alarm       = 2
  evaluation_periods        = 2
  insufficient_data_actions = []
  ok_actions                = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  threshold                 = 0
  treat_missing_data        = "notBreaching"

  metric_query {
    id          = "freeable_memory"
    return_data = false

    metric {
      metric_name = "FreeableMemory"
      namespace   = "AWS/ElastiCache"
      period      = 60
      stat        = "Average"
      dimensions = {
        CacheClusterId = each.value
      }
    }
  }

  metric_query {
    id          = "swap_usage"
    return_data = false

    metric {
      metric_name = "SwapUsage"
      namespace   = "AWS/ElastiCache"
      period      = 60
      stat        = "Average"
      dimensions = {
        CacheClusterId = each.value
      }
    }
  }

  metric_query {
    id          = "memory_pressure"
    expression  = "IF(freeable_memory < 100000000 OR swap_usage > freeable_memory, 1, 0)"
    label       = "Memory pressure"
    return_data = true
  }

  provider = aws.region
}
