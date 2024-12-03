resource "aws_cloudwatch_event_bus" "main" {
  count    = var.event_bus_enabled ? 1 : 0
  name     = var.environment_name
  provider = aws.region
}

resource "aws_cloudwatch_event_archive" "main" {
  count            = var.event_bus_enabled ? 1 : 0
  name             = var.environment_name
  event_source_arn = aws_cloudwatch_event_bus.main[0].arn
  provider         = aws.region
}

resource "aws_cloudwatch_event_rule" "receive_events_mlpa" {
  count          = var.event_bus_enabled ? 1 : 0
  name           = "${var.environment_name}-mlpa-events-to-use"
  description    = "receive events from mlpa"
  event_bus_name = aws_cloudwatch_event_bus.main[0].name

  event_pattern = jsonencode({
    source = ["opg.poas.makeregister"],
  })
  provider = aws.region
}

data "aws_kms_alias" "sqs" {
  name     = "alias/sqs-mrk"
  provider = aws.region
}

resource "aws_sqs_queue" "receive_events_queue" {
  count                             = var.event_bus_enabled ? 1 : 0
  name                              = "${var.environment_name}-receive-events-queue"
  kms_master_key_id                 = data.aws_kms_alias.sqs.target_key_id
  kms_data_key_reuse_period_seconds = 300

  visibility_timeout_seconds = 300

  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.receive_events_deadletter[0].arn
    maxReceiveCount     = 3
  })
  policy = data.aws_iam_policy_document.receive_events_queue_policy[0].json

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
    resources = ["*"]

    condition {
      test     = "ArnEquals"
      variable = "aws:SourceArn"
      values = [
        aws_cloudwatch_event_rule.receive_events_mlpa[0].arn
      ]
    }
  }
}

resource "aws_sqs_queue" "receive_events_deadletter" {
  count                             = var.event_bus_enabled ? 1 : 0
  name                              = "${var.environment_name}-receive-events-deadletter"
  kms_master_key_id                 = data.aws_kms_alias.sqs.target_key_id
  kms_data_key_reuse_period_seconds = 300
  provider                          = aws.region
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

/*
resource "aws_lambda_event_source_mapping" "reveive_events_mapping" {
  count            = var.event_bus_enabled ? 1 : 0
  event_source_arn = aws_sqs_queue.receive_events_queue[0].arn
  enabled          = false
  function_name    = var.ingress_lambda_name
  batch_size       = 10
  provider         = aws.region
}
*/
