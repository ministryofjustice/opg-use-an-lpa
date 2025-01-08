resource "aws_cloudwatch_event_bus" "main" {
  count              = var.event_bus_enabled ? 1 : 0
  name               = var.environment_name
  kms_key_identifier = var.event_reciever_kms_key_arn
  provider           = aws.region
}

resource "aws_cloudwatch_event_archive" "main" {
  count            = var.event_bus_enabled ? 1 : 0
  name             = var.environment_name
  event_source_arn = aws_cloudwatch_event_bus.main[0].arn
  provider         = aws.region
}


resource "aws_cloudwatch_event_rule" "receive_events_from_mlpa" {
  count          = var.event_bus_enabled ? 1 : 0
  name           = "${var.environment_name}-mlpa-events-to-use"
  description    = "Receive events from mlpa"
  event_bus_name = aws_cloudwatch_event_bus.main[0].name

  event_pattern = jsonencode({
    source      = ["opg.poas.makeregister"],
    detail-type = ["lpa-access-granted"]
  })

  provider = aws.region
}

resource "aws_cloudwatch_event_bus_policy" "cross_account_receive" {
  count          = length(var.receive_account_ids) > 0 && var.event_bus_enabled ? 1 : 0
  event_bus_name = aws_cloudwatch_event_bus.main[0].name
  policy         = data.aws_iam_policy_document.cross_account_receive[0].json
  provider       = aws.region
}

# Allow MLPA account to send messages
data "aws_iam_policy_document" "cross_account_receive" {
  count = var.event_bus_enabled ? 1 : 0
  statement {
    sid    = "CrossAccountAccess"
    effect = "Allow"
    actions = [
      "events:PutEvents",
    ]
    resources = [
      aws_cloudwatch_event_bus.main[0].arn
    ]

    principals {
      type        = "AWS"
      identifiers = var.receive_account_ids
    }
  }
}

resource "aws_cloudwatch_event_target" "receive_events" {
  count          = var.event_bus_enabled ? 1 : 0
  rule           = aws_cloudwatch_event_rule.receive_events_from_mlpa[0].name
  arn            = aws_sqs_queue.receive_events_queue[0].arn
  event_bus_name = aws_cloudwatch_event_bus.main[0].name
  dead_letter_config {
    arn = aws_sqs_queue.receive_events_deadletter[0].arn
  }

  provider = aws.region
}
