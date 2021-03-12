module "clsf-to-sqs" {
  source      = "./modules/lambda_function"
  lambda_name = "cslf-to-sqs"
  description = "Function to take Cloudwatch Logs Subscription Filters and send them to SQS"
  environment = local.environment
  environment_variables = {
    "QUEUE_URL" : aws_sqs_queue.ship_to_opg_metrics[0].id
  }
  image_uri                   = "${aws_ecr_repository.lambda["development/cslf_to_sqs"].repository_url}/${local.environment}/cslf_to_sqs:latest"
  lambda_role_policy_document = data.aws_iam_policy_document.lambda.json
  tags                        = local.default_tags
}

resource "aws_cloudwatch_log_group" "cslf-to-sqs" {
  name = "/aws/lambda/cslf-to-sqs"
  tags = local.default_tags
}

data "aws_iam_policy_document" "lambda" {
  statement {
    sid       = "allowLogging"
    effect    = "Allow"
    resources = [aws_cloudwatch_log_group.cslf-to-sqs.arn]
    actions = [
      "logs:CreateLogStream",
      "logs:PutLogEvents",
      "logs:DescribeLogStreams"
    ]
  }

  statement {
    sid       = "AllowECRAccess"
    effect    = "Allow"
    resources = [aws_ecr_repository.lambda["development/cslf_to_sqs"].arn]
    actions = [
      "ecr:SetRepositoryPolicy",
      "ecr:GetRepositoryPolicy",
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchGetImage",
      "ecr:BatchCheckLayerAvailability",
      "ecr:GetAuthorizationToken",
      "ecr:BatchGetImage",
      "ecr:DescribeImages",
      "ecr:DescribeRepositories",
      "ecr:ListImages",
      "ecr:PutImage",
      "ecr:InitiateLayerUpload",
      "ecr:UploadLayerPart",
      "ecr:CompleteLayerUpload",
    ]
  }
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
