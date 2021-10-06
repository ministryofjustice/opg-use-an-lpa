resource "aws_secretsmanager_secret" "notify_api_key" {
  name = "notify-api-key"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "test_secret" {
  name       = "test-key"
  kms_key_id = aws_kms_key.secrets_manager.key_id
  tags       = local.default_tags
}

resource "aws_kms_key" "secrets_manager" {
  description             = "Secrets Manager App Secrets encryption ${local.environment}"
  deletion_window_in_days = 10
  policy                  = data.aws_iam_policy_document.secrets_manager_kms.json
}

resource "aws_kms_alias" "secrets_manager_alias" {
  name          = "alias/secrets_manager_encryption"
  target_key_id = aws_kms_key.secrets_manager.key_id
}

# See the following link for further information
# https://docs.aws.amazon.com/kms/latest/developerguide/key-policies.html
data "aws_iam_policy_document" "secrets_manager_kms" {
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
    sid       = "Allow Key to be used for Decryption"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
    ]

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/operator",
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
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
      ]
    }
  }
}
