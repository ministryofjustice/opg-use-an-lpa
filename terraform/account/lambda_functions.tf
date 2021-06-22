data "aws_ecr_repository" "clsf_to_sqs" {
  name = "${local.environment}/clsf-to-sqs"
}

data "aws_ecr_repository" "ship_to_opg_metrics" {
  name = "${local.environment}/ship-to-opg-metrics"
}

module "clsf_to_sqs" {
  source            = "./modules/lambda_function"
  count             = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  lambda_name       = "clsf-to-sqs"
  description       = "Function to take Cloudwatch Logs Subscription Filters and send them to SQS"
  working_directory = "/var/task"
  environment_variables = {
    "QUEUE_URL" : aws_sqs_queue.ship_to_opg_metrics[0].id,
    "METRIC_PROJECT_NAME" : local.environment
  }
  image_uri                   = "${data.aws_ecr_repository.clsf_to_sqs.repository_url}:${var.lambda_container_version}"
  ecr_arn                     = data.aws_ecr_repository.clsf_to_sqs.arn
  lambda_role_policy_document = data.aws_iam_policy_document.clsf_to_sqs_lambda_function_policy[0].json
  tags                        = local.default_tags
}

data "aws_iam_policy_document" "clsf_to_sqs_lambda_function_policy" {
  count = local.account.ship_metrics_queue_enabled == true ? 1 : 0
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
  count             = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  lambda_name       = "ship-to-opg-metrics"
  description       = "Function to take metrics from SQS and PUT them to OPG Metrics"
  working_directory = "/var/task"
  environment_variables = {
    "OPG_METRICS_URL" : "https://${local.dns_namespace_env}api.metrics.opg.service.justice.gov.uk"
  }
  image_uri                   = "${data.aws_ecr_repository.ship_to_opg_metrics.repository_url}:${var.lambda_container_version}"
  ecr_arn                     = data.aws_ecr_repository.ship_to_opg_metrics.arn
  lambda_role_policy_document = data.aws_iam_policy_document.ship_to_opg_metrics_lambda_function_policy[0].json
  tags                        = local.default_tags
}

data "aws_iam_policy_document" "ship_to_opg_metrics_lambda_function_policy" {
  count = local.account.ship_metrics_queue_enabled == true ? 1 : 0
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
