module "redacted-logs" {
  source = "./modules/s3_bucket"

  account_name    = local.environment
  bucket_name     = "opg-use-an-lpa-redacted-logs-${local.environment}-${data.aws_region.current.name}"
  expiration_days = 400 # Log Retention is 13 Months/400 Days as Policy
  force_destroy   = false
  kms_key         = aws_kms_key.redacted_s3
  providers = {
    aws = aws
  }
}

resource "aws_kms_key" "redacted_s3" {
  description             = "KMS Key for Encrypting Redacted Logs in S3"
  deletion_window_in_days = 7
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.redacted_s3_logs_key_policy.json
}

resource "aws_kms_alias" "redacted_s3" {
  name          = "alias/redacted_logs_s3_${local.environment}"
  target_key_id = aws_kms_key.redacted_s3.key_id
}

data "aws_iam_policy_document" "redacted_s3_logs_key_policy" {
  statement {
    sid       = "Enable IAM User Permissions"
    effect    = "Allow"
    resources = ["*"]
    actions   = ["kms:*"]

    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"]
    }
  }

  statement {
    sid       = "Allow access for Key Administrators"
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
      "kms:CancelKeyDeletion",
    ]

    principals {
      type        = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/opg-use-an-lpa-ci"
      ]
    }
  }

  statement {
    sid       = "Allow use of the key"
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
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
      ]
    }
  }

  statement {
    sid       = "Allow attachment of persistent resources"
    effect    = "Allow"
    resources = ["*"]

    actions = [
      "kms:CreateGrant",
      "kms:ListGrants",
      "kms:RevokeGrant",
    ]

    principals {
      type = "AWS"

      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
      ]
    }

    condition {
      test     = "Bool"
      variable = "kms:GrantIsForAWSResource"
      values   = ["true"]
    }
  }
}
