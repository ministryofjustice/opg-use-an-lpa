data "aws_elb_service_account" "main" {
  region = "eu-west-1"
}

data "aws_iam_policy_document" "access_log" {
  statement {
    sid = "accessLogBucketAccess"
    resources = [
      aws_s3_bucket.access_log.arn,
      "${aws_s3_bucket.access_log.arn}/*",
    ]
    effect  = "Allow"
    actions = ["s3:PutObject"]
    principals {
      identifiers = [data.aws_elb_service_account.main.id]
      type        = "AWS"
    }
  }

  statement {
    sid     = "AllowSSLRequestsOnly"
    effect  = "Deny"
    actions = ["s3:*"]
    resources = [
      aws_s3_bucket.access_log.arn,
      "${aws_s3_bucket.access_log.arn}/*",
    ]
    condition {
      test     = "Bool"
      values   = ["false"]
      variable = "aws:SecureTransport"
    }
    principals {
      identifiers = ["*"]
      type        = "AWS"
    }
  }
}

resource "aws_kms_key" "access_log" {
  description             = "S3 bucket encryption key for access_log"
  deletion_window_in_days = 7
  enable_key_rotation     = true
}

resource "aws_kms_alias" "access_log" {
  name          = "alias/s3-access-log-${local.environment}"
  target_key_id = aws_kms_key.access_log.key_id
}
resource "aws_s3_bucket" "access_log" {
  bucket = "opg-ual-${local.environment}-lb-access-logs"
}

resource "aws_s3_bucket_acl" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  acl    = "private"
}

resource "aws_s3_bucket_server_side_encryption_configuration" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  rule {
    apply_server_side_encryption_by_default {
      kms_master_key_id = aws_kms_key.access_log.arn
      sse_algorithm     = "aws:kms"
    }
  }
}

resource "aws_s3_bucket_versioning" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_policy" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  policy = data.aws_iam_policy_document.access_log.json
}

resource "aws_s3_bucket_public_access_block" "access_log" {
  bucket                  = aws_s3_bucket.access_log.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}
