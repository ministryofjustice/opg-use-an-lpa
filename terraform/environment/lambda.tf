# Function to update use a lpa event statistics to stats table
module "lambda_update_statistics" {
  source      = "./modules/lambda"
  lambda_name = "update-statistics"
  environment_variables = {
    ENVIRONMENT = local.environment_name
    REGION      = data.aws_region.current.name
  }
  image_uri   = "${data.aws_ecr_repository.use_an_lpa_upload_statistics.repository_url}:${var.container_version}"
  ecr_arn     = data.aws_ecr_repository.use_an_lpa_upload_statistics.arn
  environment = local.environment_name
  kms_key     = data.aws_kms_alias.cloudwatch_encryption.target_key_arn
  timeout     = 900
  memory      = 1024
}


# Additional IAM permissions
resource "aws_iam_role_policy" "lambda_update_statistics" {
  name   = "lambda-update-statistics-${local.environment_name}"
  role   = module.lambda_update_statistics.lambda_role.id
  policy = data.aws_iam_policy_document.lambda_update_statistics.json
}

data "aws_iam_policy_document" "lambda_update_statistics" {
  statement {
    sid       = "GetMetrics"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "cloudwatch:GetMetricStatistics",
      "cloudwatch:ListMetrics",
      "cloudwatch:GetMetricData",
      "cloudwatch:GetMetricStream",
    ]
  }

  statement {
    sid    = "GetFromTables"
    effect = "Allow"
    resources = [
      aws_dynamodb_table.stats_table.arn,
      aws_dynamodb_table.user_lpa_actor_map.arn,
      aws_dynamodb_table.viewer_activity_table.arn,
      aws_dynamodb_table.viewer_codes_table.arn,
    ]
    actions = [
      "dynamodb:BatchGet*",
      "dynamodb:DescribeStream",
      "dynamodb:DescribeTable",
      "dynamodb:Get*",
      "dynamodb:Query",
      "dynamodb:Scan",
    ]
  }

  statement {
    sid    = "UpdateTables"
    effect = "Allow"
    resources = [
      aws_dynamodb_table.stats_table.arn,
    ]
    actions = [
      "dynamodb:BatchWrite*",
      "dynamodb:Delete*",
      "dynamodb:Update*",
      "dynamodb:PutItem"
    ]
  }
}

# Scheduling
resource "aws_cloudwatch_event_rule" "update_statistics" {
  name                = "${local.environment_name}-update-statistics"
  description         = "Kicks off update of statistics into dynamodb"
  schedule_expression = "cron(0 3 * * ? *)"
}

resource "aws_cloudwatch_event_target" "update_statistics" {
  rule = aws_cloudwatch_event_rule.update_statistics.name
  arn  = module.lambda_update_statistics.lambda.arn
}

resource "aws_lambda_permission" "cloudwatch_to_update_statistics_lambda" {
  statement_id  = "AllowExecutionFromCloudWatchUpdateStats"
  action        = "lambda:InvokeFunction"
  function_name = module.lambda_update_statistics.lambda.function_name
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.update_statistics.arn
}

module "event_receiver" {
  count       = local.environment.event_bus_enabled ? 1 : 0
  source      = "./modules/lambda"
  lambda_name = "event-receiver"
  environment_variables = {
    ENVIRONMENT = local.environment_name
    REGION      = data.aws_region.current.name
  }
  image_uri   = "${data.aws_ecr_repository.use_an_lpa_event_receiver.repository_url}:${var.container_version}"
  ecr_arn     = data.aws_ecr_repository.use_an_lpa_event_receiver.arn
  environment = local.environment_name
  kms_key     = data.aws_kms_alias.cloudwatch_encryption.target_key_arn
  timeout     = 900
  memory      = 128
}

resource "aws_iam_role_policy" "lambda_event_receiver" {
  count  = local.environment.event_bus_enabled ? 1 : 0
  name   = "${local.environment_name}-lambda-event-receiver"
  role   = module.event_receiver[0].lambda_role.name
  policy = data.aws_iam_policy_document.lambda_event_receiver[0].json
}


data "aws_iam_policy_document" "lambda_event_receiver" {
  count = local.environment.event_bus_enabled ? 1 : 0
  statement {
    sid    = "${local.environment_name}EventReceiverSQS"
    effect = "Allow"
    actions = [
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
      "sqs:GetQueueAttributes",
    ]
    resources = [module.eu_west_1[0].receive_events_sqs_queue_arn[0]]
  }

  statement {
    sid    = "${local.environment_name}KMSDecrypt"
    effect = "Allow"
    actions = [
      "kms:Decrypt",
      "kms:DescribeKey"
    ]
    resources = [data.aws_kms_alias.event_receiver.target_key_arn]
  }
}

resource "aws_lambda_event_source_mapping" "receive_events_mapping" {
  count            = local.environment.event_bus_enabled ? 1 : 0
  event_source_arn = module.eu_west_1[0].receive_events_sqs_queue_arn[0]
  function_name    = module.event_receiver[0].lambda_name
  enabled          = true
}

resource "aws_lambda_permission" "receive_events_permission" {
  count         = local.environment.event_bus_enabled ? 1 : 0
  statement_id  = "AllowExecutionFromSQS"
  action        = "lambda:InvokeFunction"
  function_name = module.event_receiver[0].lambda_name
  principal     = "sqs.amazonaws.com"
  source_arn    = module.eu_west_1[0].receive_events_sqs_queue_arn[0]
}
