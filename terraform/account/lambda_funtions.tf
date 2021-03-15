module "clsf_to_sqs" {
  source            = "./modules/lambda_function"
  lambda_name       = "clsf-to-sqs"
  description       = "Function to take Cloudwatch Logs Subscription Filters and send them to SQS"
  working_directory = "/var/task"
  environment_variables = {
    "QUEUE_URL" : aws_sqs_queue.ship_to_opg_metrics[0].id
  }
  image_uri                   = "${aws_ecr_repository.lambda["development/clsf-to-sqs"].repository_url}:${var.lambda_container_version}"
  ecr_arn                     = aws_ecr_repository.lambda["development/clsf-to-sqs"].arn
  lambda_role_policy_document = data.aws_iam_policy_document.clsf_to_sqs_lambda_function_policy.json
  tags                        = local.default_tags
}

data "aws_iam_policy_document" "clsf_to_sqs_lambda_function_policy" {
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
  lambda_name       = "ship-to-opg-metrics"
  description       = "Function to take metrics from SQS and PUT them to OPG Metrics"
  working_directory = "/var/task"
  environment_variables = {
    "OPG_METRICS_URL" : ""
  }
  image_uri                   = "${aws_ecr_repository.lambda["development/ship-to-opg-metrics"].repository_url}:${var.lambda_container_version}"
  ecr_arn                     = aws_ecr_repository.lambda["development/ship-to-opg-metrics"].arn
  lambda_role_policy_document = data.aws_iam_policy_document.ship_to_opg_metrics_lambda_function_policy.json
  tags                        = local.default_tags
}

data "aws_iam_policy_document" "ship_to_opg_metrics_lambda_function_policy" {
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
