data "aws_elb_service_account" "main" {
  region = data.aws_region.current.name
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
      identifiers = [data.aws_elb_service_account.main.arn]
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
  policy                  = data.aws_iam_policy_document.access_log_kms.json
}

resource "aws_kms_alias" "access_log" {
  name          = "alias/s3-access-log-${local.environment}"
  target_key_id = aws_kms_key.access_log.key_id
}

data "aws_iam_policy_document" "access_log_kms" {
  statement {
    sid       = "Enable Root account permissions on Key"
    effect    = "Allow"
    actions   = ["kms:*"]
    resources = ["*"]

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root",
      ]
    }
  }

  statement {
    sid       = "Allow Key to be used for Encryption"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
    ]

    principals {
      type = "Service"
      identifiers = [
        "logs.${data.aws_region.current.name}.amazonaws.com",
        "s3.amazonaws.com",
      ]
    }
  }

  statement {
    sid       = "Allow Key to be used by LB for Encryption"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
    ]

    principals {
      type = "AWS"
      identifiers = [
        data.aws_elb_service_account.main.arn
      ]
    }
  }

  statement {
    sid       = "Key Administrator"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Create*",
      "kms:Describe*",
      "kms:Enable*",
      "kms:List*",
      "kms:Put*",
      "kms:Update*",
      "kms:Revoke*",
      "kms:Disable*",
      "kms:Get*",
      "kms:Delete*",
      "kms:TagResource",
      "kms:UntagResource",
      "kms:ScheduleKeyDeletion",
      "kms:CancelKeyDeletion"
    ]

    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass"]
    }
  }
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
    bucket_key_enabled = true
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

data "aws_s3_bucket" "s3_access_logging" {
  bucket = "${local.account.s3_access_log_bucket_name}-${data.aws_region.current.name}"
}

resource "aws_s3_bucket_logging" "access_log" {
  bucket = aws_s3_bucket.access_log.id

  target_bucket = data.aws_s3_bucket.s3_access_logging.id
  target_prefix = "lb-access-log/"
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
