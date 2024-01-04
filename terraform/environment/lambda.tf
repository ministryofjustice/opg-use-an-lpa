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
