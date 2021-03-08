
resource "aws_sqs_queue" "ship_to_opg_metrics" {
  name                      = "${local.environment}-ship-to-opg-metrics"
  delay_seconds             = 90
  max_message_size          = 2048
  message_retention_seconds = 86400
  receive_wait_time_seconds = 10
  tags                      = local.default_tags
}

resource "aws_sqs_queue_policy" "ship_to_opg_metrics" {
  queue_url = aws_sqs_queue.ship_to_opg_metrics.id
  policy    = data.aws_iam_policy_document.ship_to_opg_metrics.json
}

data "aws_iam_policy_document" "ship_to_opg_metrics" {
  statement {
    effect    = "Allow"
    resources = [aws_sqs_queue.ship_to_opg_metrics.arn]
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
