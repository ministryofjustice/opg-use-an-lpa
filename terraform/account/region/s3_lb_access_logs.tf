# Old version of the access log bucket. The new version is suffixed with the region name. We're keeping this around for a while to ensure we don't lose any logs.
resource "aws_s3_bucket" "old_access_log" {
  count  = data.aws_region.current.name == "eu-west-1" ? 1 : 0
  bucket = "opg-ual-${var.environment_name}-lb-access-logs"

  provider = aws.region
}

resource "aws_s3_bucket" "access_log" {
  bucket = "opg-ual-${var.environment_name}-lb-access-logs-${data.aws_region.current.name}"

  provider = aws.region
}

resource "aws_s3_bucket_acl" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  acl    = "private"

  depends_on = [
    aws_s3_bucket_ownership_controls.access_log
  ]

  provider = aws.region
}

resource "aws_s3_bucket_server_side_encryption_configuration" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "aws:kms"
    }
  }

  provider = aws.region
}

resource "aws_s3_bucket_versioning" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  versioning_configuration {
    status = "Enabled"
  }

  provider = aws.region
}

data "aws_s3_bucket" "s3_access_logging" {
  bucket = "${var.account.s3_access_log_bucket_name}-${data.aws_region.current.name}"

  provider = aws.region
}

resource "aws_s3_bucket_logging" "access_log" {
  bucket = aws_s3_bucket.access_log.id

  target_bucket = data.aws_s3_bucket.s3_access_logging.id
  target_prefix = "lb-access-log/"

  provider = aws.region
}

resource "aws_s3_bucket_policy" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  policy = data.aws_iam_policy_document.access_log.json

  provider = aws.region
}

data "aws_elb_service_account" "main" {
  region = data.aws_region.current.name

  provider = aws.region
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
    sid = "accessLogDelivery"
    resources = [
      aws_s3_bucket.access_log.arn,
      "${aws_s3_bucket.access_log.arn}/*",
    ]
    effect  = "Allow"
    actions = ["s3:PutObject"]
    principals {
      identifiers = ["delivery.logs.amazonaws.com"]
      type        = "Service"
    }
    condition {
      test     = "StringEquals"
      values   = ["bucket-owner-full-control"]
      variable = "s3:x-amz-acl"
    }
  }

  statement {
    sid = "accessGetAcl"
    resources = [
      aws_s3_bucket.access_log.arn
    ]
    effect  = "Allow"
    actions = ["s3:GetBucketAcl"]
    principals {
      identifiers = ["delivery.logs.amazonaws.com"]
      type        = "Service"
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

resource "aws_s3_bucket_ownership_controls" "access_log" {
  bucket = aws_s3_bucket.access_log.id

  rule {
    object_ownership = "ObjectWriter"
  }
}

resource "aws_s3_bucket_public_access_block" "access_log" {
  bucket                  = aws_s3_bucket.access_log.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true

  provider = aws.region
}
