resource "aws_ecs_cluster" "use-an-lpa" {
  name = "${var.environment_name}-use-an-lpa"
  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  provider = aws.region
}

resource "aws_iam_role_policy" "execution_role" {
  name   = "${var.environment_name}_execution_role"
  policy = data.aws_iam_policy_document.execution_role.json
  role   = var.ecs_execution_role.id

  provider = aws.region
}

data "aws_iam_policy_document" "execution_role" {
  statement {
    effect    = "Allow"
    resources = ["*"]

    actions = [
      "ecr:GetAuthorizationToken",
      "ecr:BatchCheckLayerAvailability",
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchGetImage",
      "logs:CreateLogStream",
      "logs:PutLogEvents",
    ]
  }
  statement {
    effect = "Allow"

    resources = [data.aws_secretsmanager_secret.notify_api_key.arn]

    actions = [
      "secretsmanager:GetSecretValue",
    ]
  }
  statement {
    effect = "Allow"

    resources = [data.aws_kms_alias.secrets_manager.target_key_arn]

    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
      "kms:GenerateDataKeyPair",
      "kms:GenerateDataKeyPairWithoutPlaintext",
      "kms:GenerateDataKeyWithoutPlaintext",
      "kms:DescribeKey",
    ]
  }

  provider = aws.region
}
