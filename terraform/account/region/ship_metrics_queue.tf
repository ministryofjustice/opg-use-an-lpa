resource "aws_sqs_queue" "ship_to_opg_metrics" {
  count                             = var.account.opg_metrics.enabled ? 1 : 0
  name                              = "${var.environment_name}-ship-to-opg-metrics"
  delay_seconds                     = 90
  max_message_size                  = 2048
  message_retention_seconds         = 86400
  receive_wait_time_seconds         = 10
  kms_master_key_id                 = "alias/aws/sqs"
  kms_data_key_reuse_period_seconds = 300

  provider = aws.region
}

resource "aws_sqs_queue_policy" "ship_to_opg_metrics" {
  count     = var.account.opg_metrics.enabled ? 1 : 0
  queue_url = aws_sqs_queue.ship_to_opg_metrics[0].id
  policy    = data.aws_iam_policy_document.ship_to_opg_metrics_queue_policy[0].json

  provider = aws.region
}

data "aws_iam_policy_document" "ship_to_opg_metrics_queue_policy" {
  count = var.account.opg_metrics.enabled ? 1 : 0
  statement {
    effect    = "Allow"
    resources = [aws_sqs_queue.ship_to_opg_metrics[0].arn]
    actions = [
      "sqs:ChangeMessageVisibility",
      "sqs:DeleteMessage",
      "sqs:GetQueueAttributes",
      "sqs:GetQueueUrl",
      "sqs:ListQueueTags",
      "sqs:ReceiveMessage",
      "sqs:SendMessage",
    ]
    principals {
      type        = "AWS"
      identifiers = [var.account.account_id]
    }
  }
}

resource "aws_lambda_event_source_mapping" "ship_to_opg_metrics" {
  count            = var.account.opg_metrics.enabled ? 1 : 0
  event_source_arn = aws_sqs_queue.ship_to_opg_metrics[0].arn
  function_name    = module.ship_to_opg_metrics[0].lambda_function.arn

  provider = aws.region
}
