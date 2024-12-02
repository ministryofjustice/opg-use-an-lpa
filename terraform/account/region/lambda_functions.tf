data "aws_ecr_repository" "clsf_to_sqs" {
  name     = "opg-metrics/clsf-to-sqs"
  provider = aws.management
}

data "aws_ecr_repository" "ship_to_opg_metrics" {
  name     = "opg-metrics/ship-to-opg-metrics"
  provider = aws.management
}

data "aws_ecr_repository" "ingestion_repo" {
  name     = "use_an_lpa/ingestion_lambda"
  provider = aws.management
}

module "ingestion_lambda" {
  source            = "./modules/lambda_function"
  lambda_name       = "ingestion-lambda-${data.aws_region.current.name}"
  working_directory = "/"

  image_uri                           = "${data.aws_ecr_repository.ingestion_repo.repository_url}:${var.lambda_container_version}"
  ecr_arn                             = data.aws_ecr_repository.ingestion_repo.arn
  lambda_role_policy_document         = data.aws_iam_policy_document.ingestion_lambda_function_policy.json
  aws_cloudwatch_log_group_kms_key_id = data.aws_kms_alias.cloudwatch_mrk.arn

  providers = {
    aws = aws.region
  }
}

data "aws_iam_policy_document" "ingestion_lambda_function_policy" {
  statement {
    sid       = "AllowSQSAccess"
    effect    = "Allow"
    resources = [aws_sqs_queue.ship_to_opg_metrics[0].arn]
    actions = [
      "sqs:SendMessage",
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
    ]
  }
}

module "clsf_to_sqs" {
  source            = "./modules/lambda_function"
  count             = var.account.opg_metrics.enabled ? 1 : 0
  lambda_name       = "clsf-to-sqs-${data.aws_region.current.name}"
  working_directory = "/var/task"
  environment_variables = {
    "QUEUE_URL" : aws_sqs_queue.ship_to_opg_metrics[0].id,
    "METRIC_PROJECT_NAME" : "use-an-lpa",
    "METRIC_CATEGORY" : "kpi",
    "METRIC_SUBCATEGORY" : "service",
    "METRIC_ENVIRONMENT" : var.environment_name
  }
  image_uri                           = "${data.aws_ecr_repository.clsf_to_sqs.repository_url}:${var.lambda_container_version}"
  ecr_arn                             = data.aws_ecr_repository.clsf_to_sqs.arn
  lambda_role_policy_document         = data.aws_iam_policy_document.clsf_to_sqs_lambda_function_policy[0].json
  aws_cloudwatch_log_group_kms_key_id = data.aws_kms_alias.cloudwatch_mrk.arn

  providers = {
    aws = aws.region
  }
}

data "aws_iam_policy_document" "clsf_to_sqs_lambda_function_policy" {
  count = var.account.opg_metrics.enabled ? 1 : 0
  statement {
    sid       = "AllowSQSAccess"
    effect    = "Allow"
    resources = [aws_sqs_queue.ship_to_opg_metrics[0].arn]
    actions = [
      "sqs:SendMessage",
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
    ]
  }
}

data "aws_secretsmanager_secret_version" "opg_metrics_api_key" {
  count         = var.account.opg_metrics.enabled ? 1 : 0
  secret_id     = data.aws_secretsmanager_secret.opg_metrics_api_key[0].id
  version_stage = "AWSCURRENT"
  provider      = aws.shared
}

data "aws_secretsmanager_secret" "opg_metrics_api_key" {
  count    = var.account.opg_metrics.enabled ? 1 : 0
  name     = var.account.opg_metrics.api_key_secretsmanager_name
  provider = aws.shared
}

data "aws_kms_alias" "opg_metrics_api_key_encryption" {
  name     = "alias/opg_metrics_api_key_encryption"
  provider = aws.shared
}

module "ship_to_opg_metrics" {
  source            = "./modules/lambda_function"
  count             = var.account.opg_metrics.enabled ? 1 : 0
  lambda_name       = "ship-to-opg-metrics-${data.aws_region.current.name}"
  working_directory = "/var/task"
  environment_variables = {
    "OPG_METRICS_URL" : var.account.opg_metrics.endpoint_url
    "SECRET_ARN" : data.aws_secretsmanager_secret_version.opg_metrics_api_key[0].arn
  }
  image_uri                           = "${data.aws_ecr_repository.ship_to_opg_metrics.repository_url}:${var.lambda_container_version}"
  ecr_arn                             = data.aws_ecr_repository.ship_to_opg_metrics.arn
  lambda_role_policy_document         = data.aws_iam_policy_document.ship_to_opg_metrics_lambda_function_policy[0].json
  aws_cloudwatch_log_group_kms_key_id = data.aws_kms_alias.cloudwatch_mrk.arn

  providers = {
    aws = aws.region
  }
}

data "aws_iam_policy_document" "ship_to_opg_metrics_lambda_function_policy" {
  count = var.account.opg_metrics.enabled ? 1 : 0
  statement {
    sid       = "AllowSQSAccess"
    effect    = "Allow"
    resources = [aws_sqs_queue.ship_to_opg_metrics[0].arn]
    actions = [
      "sqs:SendMessage",
      "sqs:ReceiveMessage",
      "sqs:DeleteMessage",
      "sqs:GetQueueAttributes",
    ]
  }

  statement {
    sid    = "AllowSecretsManagerAccess"
    effect = "Allow"
    resources = [
      data.aws_secretsmanager_secret.opg_metrics_api_key[0].arn,
      "arn:aws:secretsmanager:eu-west-1:679638075911:secret:opg-metrics-api-key/use-a-lasting-power-of-attorney-development"
    ]
    actions = [
      "secretsmanager:GetSecretValue",
    ]
  }
  statement {
    sid       = "AllowKMSDecrypt"
    effect    = "Allow"
    resources = [data.aws_kms_alias.opg_metrics_api_key_encryption.target_key_arn]
    actions = [
      "kms:Decrypt",
    ]
  }
}
