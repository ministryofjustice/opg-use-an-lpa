resource "aws_sqs_queue" "receive_events_queue" {
  count = var.event_bus_enabled ? 1 : 0
  name  = "${var.environment_name}-receive-events-queue"
  //  kms_master_key_id                 = data.aws_kms_alias.event_receiver_mrk.target_key_id
  //  kms_data_key_reuse_period_seconds = 300

  visibility_timeout_seconds = var.queue_visibility_timeout

  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.receive_events_deadletter[0].arn
    maxReceiveCount     = 3
  })

  provider = aws.region
}

resource "aws_sqs_queue_policy" "receive_events_queue_policy" {
  count     = var.event_bus_enabled ? 1 : 0
  queue_url = aws_sqs_queue.receive_events_queue[0].id
  policy    = data.aws_iam_policy_document.receive_events_queue_policy[0].json

  provider = aws.region
}

resource "aws_sqs_queue" "receive_events_deadletter" {
  count = var.event_bus_enabled ? 1 : 0
  name  = "${var.environment_name}-receive-events-deadletter"
  //  kms_master_key_id                 = data.aws_kms_alias.event_receiver_mrk.target_key_id
  //  kms_data_key_reuse_period_seconds = 300
  provider = aws.region
}

resource "aws_sqs_queue_redrive_allow_policy" "receive_events_redrive_allow_policy" {
  count     = var.event_bus_enabled ? 1 : 0
  queue_url = aws_sqs_queue.receive_events_deadletter[0].id

  redrive_allow_policy = jsonencode({
    redrivePermission = "byQueue",
    sourceQueueArns   = [aws_sqs_queue.receive_events_queue[0].arn]
  })
  provider = aws.region
}

data "aws_iam_policy_document" "receive_events_queue_policy" {
  count = var.event_bus_enabled ? 1 : 0
  statement {
    sid    = "${var.current_region}-ReceiveFromMLPA"
    effect = "Allow"

    principals {
      type        = "Service"
      identifiers = ["events.amazonaws.com"]
    }

    actions   = ["sqs:SendMessage"]
    resources = [aws_sqs_queue.receive_events_queue[0].arn]
  }
}
