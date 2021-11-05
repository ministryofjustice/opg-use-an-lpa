data "aws_ecr_repository" "clsf_to_sqs" {
  name     = "opg-metrics/clsf-to-sqs"
  provider = aws.management
}

data "aws_ecr_repository" "ship_to_opg_metrics" {
  name     = "opg-metrics/ship-to-opg-metrics"
  provider = aws.management
}

module "clsf_to_sqs" {
  source            = "./modules/lambda_function"
  count             = local.account.opg_metrics.enabled == true ? 1 : 0
  lambda_name       = "clsf-to-sqs"
  description       = "Function to take Cloudwatch Logs Subscription Filters and send them to SQS"
  working_directory = "/var/task"
  environment_variables = {
    "QUEUE_URL" : aws_sqs_queue.ship_to_opg_metrics[0].id,
    "METRIC_PROJECT_NAME" : "use-an-lpa",
    "METRIC_CATEGORY" : "kpi",
    "METRIC_SUBCATEGORY" : "service",
    "METRIC_ENVIRONMENT" : local.environment
  }
  image_uri                           = "${data.aws_ecr_repository.clsf_to_sqs.repository_url}:${var.lambda_container_version}"
  ecr_arn                             = data.aws_ecr_repository.clsf_to_sqs.arn
  lambda_role_policy_document         = data.aws_iam_policy_document.clsf_to_sqs_lambda_function_policy[0].json
  aws_cloudwatch_log_group_kms_key_id = aws_kms_key.cloudwatch.arn
}

data "aws_secretsmanager_secret_version" "opg_metrics_api_key" {
  count     = local.account.opg_metrics.enabled == true ? 1 : 0
  secret_id = data.aws_secretsmanager_secret.opg_metrics_api_key[0].id
  provider  = aws.shared
}

data "aws_secretsmanager_secret" "opg_metrics_api_key" {
  count    = local.account.opg_metrics.enabled == true ? 1 : 0
  name     = local.account.opg_metrics.api_key_secretsmanager_name
  provider = aws.shared
}

data "aws_iam_policy_document" "clsf_to_sqs_lambda_function_policy" {
  count = local.account.opg_metrics.enabled == true ? 1 : 0
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

module "ship_to_opg_metrics" {
  source            = "./modules/lambda_function"
  count             = local.account.opg_metrics.enabled == true ? 1 : 0
  lambda_name       = "ship-to-opg-metrics"
  description       = "Function to take metrics from SQS and PUT them to OPG Metrics"
  working_directory = "/var/task"
  environment_variables = {
    "OPG_METRICS_URL" : local.account.opg_metrics.endpoint_url
    "SECRET_ID" : data.aws_secretsmanager_secret_version.opg_metrics_api_key[0].secret_id
  }
  image_uri                           = "${data.aws_ecr_repository.ship_to_opg_metrics.repository_url}:${var.lambda_container_version}"
  ecr_arn                             = data.aws_ecr_repository.ship_to_opg_metrics.arn
  lambda_role_policy_document         = data.aws_iam_policy_document.ship_to_opg_metrics_lambda_function_policy[0].json
  aws_cloudwatch_log_group_kms_key_id = aws_kms_key.cloudwatch.arn
}

data "aws_iam_policy_document" "ship_to_opg_metrics_lambda_function_policy" {
  count = local.account.opg_metrics.enabled == true ? 1 : 0
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
}
