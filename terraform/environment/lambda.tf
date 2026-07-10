# Function to update use a lpa event statistics to stats table
module "lambda_update_statistics" {
  source      = "./modules/lambda"
  lambda_name = "update-statistics"
  environment_variables = {
    ENVIRONMENT = local.environment_name
    REGION      = data.aws_region.current.region
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
      "dynamodb:PutItem",
    ]
  }

  statement {
    sid    = "DynamoKMSAccess"
    effect = "Allow"
    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
    ]
    resources = [
      data.aws_kms_alias.dynamodb_cmk.target_key_arn,
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
    ENVIRONMENT              = local.environment_name
    REGION                   = data.aws_region.current.region
    USER_LPA_ACTOR_MAP_TABLE = "${local.environment_name}-UserLpaActorMap"
    ACTOR_USERS_TABLE        = "${local.environment_name}-ActorUsers"
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

  statement {
    sid    = "DynamoDBTableAccess"
    effect = "Allow"
    resources = [
      aws_dynamodb_table.user_lpa_actor_map.arn,
      "${aws_dynamodb_table.user_lpa_actor_map.arn}/index/UserIndex",
      aws_dynamodb_table.use_users_table.arn,
      "${aws_dynamodb_table.use_users_table.arn}/index/IdentityIndex",
    ]
    actions = [
      "dynamodb:PutItem",
      "dynamodb:GetItem",
      "dynamodb:Query",
      "dynamodb:UpdateItem"
    ]
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

module "duplicate_accounts" {
  count       = local.environment.duplicate_accounts_lambda ? 1 : 0
  source      = "./modules/lambda"
  lambda_name = "duplicate-accounts"
  environment_variables = {
    BUCKET           = aws_s3_bucket.duplicate_accounts[0].bucket
    ENVIRONMENT_NAME = local.environment_name
    WORK_FILE_PREFIX = "todo"
    PLAN_FILE_PREFIX = "plan"
  }
  image_uri   = "${data.aws_ecr_repository.duplicate_accounts.repository_url}@${data.aws_ecr_image.duplicate_accounts.image_digest}"
  ecr_arn     = data.aws_ecr_repository.duplicate_accounts.arn
  environment = local.environment_name
  kms_key     = data.aws_kms_alias.cloudwatch_encryption.target_key_arn
  timeout     = 900
  memory      = 1024
}

resource "aws_iam_role_policy" "duplicate_accounts" {
  count  = local.environment.duplicate_accounts_lambda ? 1 : 0
  name   = "duplicate_accounts-${local.environment_name}"
  role   = module.duplicate_accounts[0].lambda_role.id
  policy = data.aws_iam_policy_document.duplicate_accounts_bucket_policy[0].json
}

data "aws_iam_policy_document" "duplicate_accounts_bucket_policy" {
  count = local.environment.duplicate_accounts_lambda ? 1 : 0
  statement {
    sid    = "S3Bucket"
    effect = "Allow"
    resources = [
      aws_s3_bucket.duplicate_accounts[0].arn,
      "${aws_s3_bucket.duplicate_accounts[0].arn}/*",
    ]
    actions = [
      "s3:ListBucket",
      "s3:GetObject",
      "s3:PutObject",
      "s3:DeleteObject",
    ]
  }

  statement {
    sid    = "DynamoTable"
    effect = "Allow"
    resources = [
      aws_dynamodb_table.user_lpa_actor_map.arn,
      aws_dynamodb_table.viewer_codes_table.arn,
      aws_dynamodb_table.use_users_table.arn,
    ]
    actions = [
      "dynamodb:GetItem",
      "dynamodb:Query",
      "dynamodb:UpdateItem",
      "dynamodb:PutItem",
      "dynamodb:DeleteItem",
      "dynamodb:DescribeTable",
    ]
  }
}

resource "aws_s3_bucket" "duplicate_accounts" {
  count  = local.environment.duplicate_accounts_lambda ? 1 : 0
  bucket = "opg-use-an-lpa-duplicate_accounts-${local.environment_name}"
}

resource "aws_s3_bucket_public_access_block" "duplicate_accounts" {
  count  = local.environment.duplicate_accounts_lambda ? 1 : 0
  bucket = aws_s3_bucket.duplicate_accounts[0].id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_policy" "duplicate_accounts" {
  count      = local.environment.duplicate_accounts_lambda ? 1 : 0
  depends_on = [aws_s3_bucket_public_access_block.duplicate_accounts[0]]
  bucket     = aws_s3_bucket.duplicate_accounts[0].id
  policy     = data.aws_iam_policy_document.duplicate_accounts_bucket_policy[0].json
}

data "aws_iam_policy_document" "duplicate_accounts_bucket_policy" {
  count = local.environment.duplicate_accounts_lambda ? 1 : 0

  statement {
    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass"]
    }
    actions = [
      "s3:ListBucket",
      "s3:GetObject"
    ]
    resources = [
      aws_s3_bucket.duplicate_accounts[0].arn,
      "${aws_s3_bucket.duplicate_accounts[0].arn}/*",
    ]
  }
}
