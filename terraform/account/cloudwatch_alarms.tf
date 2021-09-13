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

data "aws_cloudwatch_log_group" "cloudtrail" {
  name = "opg_use_an_lpa_cloudtrail_${local.account_name}"
}

resource "aws_cloudwatch_log_metric_filter" "root_account_usage" {
  name           = "RootConsoleLogin"
  pattern        = "{$.userIdentity.type=\"Root\" && $.userIdentity.invokedBy NOT EXISTS && $.eventType !=\"AwsServiceEvent\"}"
  log_group_name = data.aws_cloudwatch_log_group.cloudtrail.name
  metric_transformation {
    name      = "EventCount"
    namespace = "use-an-lpa/Cloudtrail"
    value     = "1"
  }
}

resource "aws_cloudwatch_metric_alarm" "root_account_usage" {
  actions_enabled     = true
  alarm_name          = "${local.account_name} root console login check"
  alarm_actions       = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  ok_actions          = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
  alarm_description   = "root login usage count"
  namespace           = "use-an-lpa/Cloudtrail"
  metric_name         = "EventCount"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  period              = 60
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  statistic           = "Sum"
  tags                = local.default_tags
  threshold           = 1
  treat_missing_data  = "notBreaching"
}
