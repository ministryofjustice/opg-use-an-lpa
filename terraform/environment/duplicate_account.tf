module "duplicate_accounts" {
  count       = local.environment.duplicate_accounts_lambda ? 1 : 0
  source      = "./modules/lambda"
  lambda_name = "duplicate-accounts"
  environment_variables = {
    BUCKET           = data.aws_s3_bucket.ual_athena_query_results.id,
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

data "aws_s3_bucket" "ual_athena_query_results" {
  bucket = "ual-athena-query-results"
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
      data.aws_s3_bucket.ual_athena_query_results.arn,
      "${data.aws_s3_bucket.ual_athena_query_results.arn}/plan/*",
      "${data.aws_s3_bucket.ual_athena_query_results.arn}/todo/*",
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
      "dynamodb:TransactWriteItems",
    ]
  }


}
