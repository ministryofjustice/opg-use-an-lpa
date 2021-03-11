resource "aws_cloudwatch_event_target" "ship_to_metrics_queue" {
  count     = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  target_id = "ship-to-metrics-queue"
  rule      = aws_cloudwatch_event_rule.event_code_cloudwatch_logs[0].name
  arn       = data.aws_sqs_queue.ship_to_opg_metrics[0].arn
}

resource "aws_cloudwatch_event_rule" "event_code_cloudwatch_logs" {
  count       = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  name        = "capture-event-code-logs"
  description = "Capture all Account Created events"

  event_pattern = <<PATTERN
{
  "source": [
    "aws.logs"
  ],
  "detail": {
    "$.context.event_code": [ { "exists": true  } ]
  }
}
PATTERN
}
