
resource "aws_sqs_queue" "ship_to_opg_metrics" {
  count                     = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  name                      = "${local.environment}-ship-to-opg-metrics"
  delay_seconds             = 90
  max_message_size          = 2048
  message_retention_seconds = 86400
  receive_wait_time_seconds = 10
  tags                      = local.default_tags
}

resource "aws_sqs_queue_policy" "ship_to_opg_metrics" {
  count     = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  queue_url = aws_sqs_queue.ship_to_opg_metrics[0].id
  policy    = data.aws_iam_policy_document.ship_to_opg_metrics[0].json
}

data "aws_iam_policy_document" "ship_to_opg_metrics" {
  count = local.account.ship_metrics_queue_enabled == true ? 1 : 0
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
      identifiers = [local.account.account_id]
    }
  }
}
