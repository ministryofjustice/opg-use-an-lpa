module "lambda_cleanup_data" {
  source      = "./modules/lambda"
  lambda_name = "cleanup-data-lambda"
  environment_variables = {
    REGION     = data.aws_region.current.region
    TABLE_NAME = aws_dynamodb_table.use_users_table.name
  }
  image_uri   = "${data.aws_ecr_repository.cleanup_data.repository_url}@${data.aws_ecr_image.cleanup_data.image_digest}"
  ecr_arn     = data.aws_ecr_repository.cleanup_data.arn
  environment = local.environment_name
  kms_key     = data.aws_kms_alias.cloudwatch_encryption.target_key_arn
  timeout     = 900
  memory      = 1024
}

resource "aws_iam_role_policy" "lambda_cleanup_data" {
  name   = "lambda-cleanup-data-${local.environment_name}"
  role   = module.lambda_cleanup_data.lambda_role.id
  policy = data.aws_iam_policy_document.lambda_cleanup_data.json
}

data "aws_iam_policy_document" "lambda_cleanup_data" {
  statement {
    sid       = "LambdaAccessDynamoDB"
    effect    = "Allow"
    resources = [aws_dynamodb_table.use_users_table.arn]
    actions = [
      "dynamodb:Scan",
      "dynamodb:BatchWriteItem",
    ]
  }

  statement {
    sid       = "LambdaKMSDecrypt"
    effect    = "Allow"
    resources = [data.aws_kms_alias.dynamodb_cmk.target_key_arn]
    actions = [
      "kms:Decrypt",
    ]
  }
}

resource "aws_lambda_permission" "allow_breakglass_invoke" {
  statement_id  = "AllowBreakglassInvoke"
  action        = "lambda:InvokeFunction"
  function_name = module.lambda_cleanup_data.lambda_name
  principal     = "arn:aws:iam::${local.environment.account_id}:role/breakglass"
}

data "aws_kms_alias" "dynamodb_cmk" {
  name = "alias/dynamodb-encryption-key-${local.environment.account_name}"
}
