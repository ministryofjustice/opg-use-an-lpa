
module "lambda_backfill" {
  count       = local.environment.deploy_backfill_lambda ? 1 : 0
  source      = "./modules/lambda"
  lambda_name = "backfill-lambda"
  environment_variables = {
    REGION      = data.aws_region.current.region
    TABLE_NAME  = aws_dynamodb_table.use_users_table.name
    BUCKET_NAME = aws_s3_bucket.lambda_backfill[0].id
  }
  image_uri   = "${data.aws_ecr_repository.backfill.repository_url}@${data.aws_ecr_image.backfill.image_digest}"
  ecr_arn     = data.aws_ecr_repository.backfill.arn
  environment = local.environment_name
  kms_key     = data.cloudwatch_log_group.lambda_backfill[0].kms_key_id
  timeout     = 900
  memory      = 1024
}

resource "aws_iam_role_policy" "lambda_backfill" {
  count  = local.environment.deploy_backfill_lambda ? 1 : 0
  name   = "lambda-backfill-${local.environment_name}"
  role   = module.lambda_backfill[0].lambda_role.id
  policy = data.aws_iam_policy_document.lambda_backfill[0].json
}

data "aws_iam_policy_document" "lambda_backfill" {
  count = local.environment.deploy_backfill_lambda ? 1 : 0
  statement {
    sid    = "LambdaAccessS3Bucket"
    effect = "Allow"
    resources = [
      aws_s3_bucket.lambda_backfill[0].arn,
      "${aws_s3_bucket.lambda_backfill[0].arn}/*",
    ]
    actions = [
      "s3:GetObject",
      "s3:DeleteObject",
      "s3:ListBucket",
      "s3:PutObject",
      "s3:AbortMultipartUpload"
    ]
  }

  statement {
    sid       = "BackfillKMSDecrypt"
    effect    = "Allow"
    resources = [data.aws_kms_alias.lambda_backfill.target_key_arn]
    actions = [
      "kms:Decrypt",
      "kms:DescribeKey"
    ]
  }
}

resource "aws_s3_bucket" "lambda_backfill" {
  count  = local.environment.deploy_backfill_lambda ? 1 : 0
  bucket = "opg-use-an-lpa-lambda-backfill-${local.environment_name}"
}

resource "aws_s3_bucket_public_access_block" "lambda_backfill" {
  count                   = local.environment.deploy_backfill_lambda ? 1 : 0
  bucket                  = aws_s3_bucket.lambda_backfill[0].id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_policy" "lambda_backfill" {
  count      = local.environment.deploy_backfill_lambda ? 1 : 0
  depends_on = [aws_s3_bucket_public_access_block.lambda_backfill[0]]
  bucket     = aws_s3_bucket.lambda_backfill[0].id
  policy     = data.aws_iam_policy_document.lambda_backfill_bucket_policy[0].json
}

data "aws_iam_policy_document" "lambda_backfill_bucket_policy" {
  count = local.environment.deploy_backfill_lambda ? 1 : 0

  statement {
    sid    = "AllowBackfillLambdaRole"
    effect = "Allow"
    principals {
      type        = "AWS"
      identifiers = [module.lambda_backfill[0].lambda_role.arn]
    }
    actions = [
      "s3:GetObject",
      "s3:DeleteObject",
      "s3:ListBucket",
      "s3:PutObject",
      "s3:AbortMultipartUpload"
    ]
    resources = [
      aws_s3_bucket.lambda_backfill[0].arn,
      "${aws_s3_bucket.lambda_backfill[0].arn}/*",
    ]
  }

  statement {
    sid    = "AllowBreakglassAccess"
    effect = "Allow"
    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${local.environment.account_id}:role/breakglass"]
    }
    actions = [
      "s3:ListBucket",
      "s3:GetObject"
    ]
    resources = [
      aws_s3_bucket.lambda_backfill[0].arn,
      "${aws_s3_bucket.lambda_backfill[0].arn}/*",
    ]
  }

  statement {
    sid = "AllowDynamoDBExportBucketAccess"
    principals {
      type        = "Service"
      identifiers = ["dynamodb.amazonaws.com"]
    }
    actions = [
      "s3:AbortMultipartUpload",
      "s3:PutObject",
      "s3:PutObjectAcl"
    ]
    resources = [
      aws_s3_bucket.lambda_backfill[0].arn,
      "${aws_s3_bucket.lambda_backfill[0].arn}/*"
    ]

    condition {
      test     = "StringEquals"
      variable = "aws:SourceAccount"
      values   = [data.aws_caller_identity.current.account_id]
    }

    condition {
      test     = "ArnLike"
      variable = "aws:SourceArn"
      values   = [aws_dynamodb_table.use_users_table.arn]
    }
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "lambda_backfill" {
  count  = local.environment.deploy_backfill_lambda ? 1 : 0
  bucket = aws_s3_bucket.lambda_backfill[0].id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm     = "aws:kms"
      kms_master_key_id = data.aws_kms_alias.lambda_backfill.arn
    }
    bucket_key_enabled = true
  }
}

data "aws_kms_alias" "lambda_backfill" {
  name = "alias/lambda-backfill-${local.environment.account_name}"
}

data "aws_caller_identity" "current" {}
