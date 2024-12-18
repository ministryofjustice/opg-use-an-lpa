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
  count          = length(var.receive_account_ids) > 0 ? 1 : 0
  event_bus_name = aws_cloudwatch_event_bus.main.name
  policy         = data.aws_iam_policy_document.cross_account_receive.json
  provider       = aws.region
}

# Allow MLPA account to send messages
data "aws_iam_policy_document" "cross_account_receive" {
  statement {
    sid    = "CrossAccountAccess"
    effect = "Allow"
    actions = [
      "events:PutEvents",
    ]
    resources = [
      aws_cloudwatch_event_bus.main.arn
    ]

    principals {
      type        = "AWS"
      identifiers = var.receive_account_ids
    }
  }
}

resource "aws_cloudwatch_event_target" "receive_events" {
  count = var.event_bus_enabled ? 1 : 0
  rule  = aws_cloudwatch_event_rule.receive_events_from_mlpa[0].name
  arn   = aws_sqs_queue.receive_events_queue.arn
}
