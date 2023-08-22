resource "aws_secretsmanager_secret" "gov_uk_onelogin_identity_private_key" {
  name       = "gov-uk-onelogin-identity-private-key"
  kms_key_id = aws_kms_key.secrets_manager.key_id
}

resource "aws_secretsmanager_secret" "gov_uk_onelogin_identity_public_key" {
  name       = "gov-uk-onelogin-identity-public-key"
  kms_key_id = aws_kms_key.secrets_manager.key_id
}

resource "aws_secretsmanager_secret_version" "gov_uk_onelogin_identity_private_key" {
  secret_id     = aws_secretsmanager_secret.gov_uk_onelogin_identity_private_key.id
  secret_string = tls_private_key.onelogin_identity.private_key_pem
}

resource "aws_secretsmanager_secret_version" "gov_uk_onelogin_identity_public_key" {
  secret_id     = aws_secretsmanager_secret.gov_uk_onelogin_identity_public_key.id
  secret_string = trimspace(tls_private_key.onelogin_identity.public_key_pem)
}

resource "aws_secretsmanager_secret" "notify_api_key" {
  name       = "notify-api-key"
  kms_key_id = aws_kms_key.secrets_manager.key_id
}

resource "aws_secretsmanager_secret" "notify_api_key_demo" {
  count      = local.account_name == "development" ? 1 : 0
  name       = "notify-api-key-demo"
  kms_key_id = aws_kms_key.secrets_manager.key_id
}

resource "aws_kms_key" "secrets_manager" {
  description             = "Secrets Manager encryption ${local.environment}"
  deletion_window_in_days = 10
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.secrets_manager_kms.json
}

resource "aws_kms_alias" "secrets_manager_alias" {
  name          = "alias/secrets_manager_encryption"
  target_key_id = aws_kms_key.secrets_manager.key_id
}

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
