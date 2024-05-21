resource "aws_secretsmanager_secret" "gov_uk_onelogin_identity_private_key" {
  name       = "gov-uk-onelogin-identity-private-key"
  kms_key_id = module.secrets_manager_mrk.key_id

  replica {
    kms_key_id = module.secrets_manager_mrk.key_id
    region     = "eu-west-2"
  }
}

resource "aws_secretsmanager_secret" "gov_uk_onelogin_identity_public_key" {
  name       = "gov-uk-onelogin-identity-public-key"
  kms_key_id = module.secrets_manager_mrk.key_id

  replica {
    kms_key_id = module.secrets_manager_mrk.key_id
    region     = "eu-west-2"
  }
}

resource "aws_secretsmanager_secret_version" "gov_uk_onelogin_identity_private_key" {
  secret_id     = aws_secretsmanager_secret.gov_uk_onelogin_identity_private_key.id
  secret_string = tls_private_key.onelogin_auth_pk.private_key_pem
}

resource "aws_secretsmanager_secret_version" "gov_uk_onelogin_identity_public_key" {
  secret_id     = aws_secretsmanager_secret.gov_uk_onelogin_identity_public_key.id
  secret_string = trimspace(tls_private_key.onelogin_auth_pk.public_key_pem)
}

resource "aws_secretsmanager_secret" "lpa_data_store_private_key" {
  name       = "lpa-data-store-private-key"
  kms_key_id = module.secrets_manager_mrk.key_id

  replica {
    kms_key_id = module.secrets_manager_mrk.key_id
    region     = "eu-west-2"
  }
}

resource "aws_secretsmanager_secret" "lpa_data_store_public_key" {
  name       = "lpa-data-store-public-key"
  kms_key_id = module.secrets_manager_mrk.key_id

  replica {
    kms_key_id = module.secrets_manager_mrk.key_id
    region     = "eu-west-2"
  }
}

resource "aws_secretsmanager_secret_version" "lpa_data_store_private_key" {
  secret_id     = aws_secretsmanager_secret.lpa_data_store_private_key.id
  secret_string = tls_private_key.lpa_data_store_pk
}

resource "aws_secretsmanager_secret_version" "lpa_data_store_public_key" {
  secret_id     = aws_secretsmanager_secret.lpa_data_store_public_key.id
  secret_string = trimspace(tls_private_key.lpa_data_store_pk.public_key_pem)
}

resource "aws_secretsmanager_secret" "gov_uk_onelogin_client_id" {
  name       = "gov-uk-onelogin-client-id"
  kms_key_id = module.secrets_manager_mrk.key_id

  replica {
    kms_key_id = module.secrets_manager_mrk.key_id
    region     = "eu-west-2"
  }
}

resource "aws_secretsmanager_secret_version" "gov_uk_onelogin_client_id" {
  secret_id     = aws_secretsmanager_secret.gov_uk_onelogin_client_id.id
  secret_string = "DEFAULT"

  lifecycle {
    ignore_changes = [
      secret_string,
    ]
  }
}


resource "aws_secretsmanager_secret" "notify_api_key" {
  name       = "notify-api-key"
  kms_key_id = module.secrets_manager_mrk.key_id

  replica {
    kms_key_id = module.secrets_manager_mrk.key_id
    region     = "eu-west-2"
  }
}

resource "aws_secretsmanager_secret" "notify_api_key_demo" {
  count      = local.account_name == "development" ? 1 : 0
  name       = "notify-api-key-demo"
  kms_key_id = module.secrets_manager_mrk.key_id

  replica {
    kms_key_id = module.secrets_manager_mrk.key_id
    region     = "eu-west-2"
  }
}

module "secrets_manager_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "Secrets Manager encryption ${local.environment}"
  key_policy              = data.aws_iam_policy_document.secrets_manager_kms.json
  key_alias               = "secrets_manager_encryption-mrk"
  deletion_window_in_days = 10

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
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
