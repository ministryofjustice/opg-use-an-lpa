module "sessions_viewer_mrk" {
  source                  = "./modules/multi_region_kms"
  key_description         = "Managers keys for sessions in Viewer"
  key_alias               = "sessions-viewer-mrk"
  deletion_window_in_days = 7

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}

module "sessions_actor_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "Managers keys for sessions in Actor"
  key_alias               = "sessions-actor-mrk"
  deletion_window_in_days = 7

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}

module "event_receiver_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "KMS key for received events"
  key_alias               = "event-receiver-mrk"
  key_policy              = data.aws_iam_policy_document.event_receiver_kms.json
  deletion_window_in_days = 7

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}

module "cloudwatch_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "Cloudwatch encryption ${local.environment}"
  key_alias               = "cloudwatch-encryption-mrk"
  deletion_window_in_days = 10
  key_policy              = data.aws_iam_policy_document.cloudwatch_kms.json

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}

# No longer used but kept to keep regional KMS keys
resource "aws_kms_key" "sessions_viewer" {
  description             = "Managers keys for sessions in Viewer"
  deletion_window_in_days = 7
  enable_key_rotation     = true
}

resource "aws_kms_alias" "sessions_viewer" {
  name          = "alias/sessions-viewer"
  target_key_id = aws_kms_key.sessions_viewer.key_id
}

resource "aws_kms_key" "sessions_actor" {
  description             = "Managers keys for sessions in Actor"
  deletion_window_in_days = 7
  enable_key_rotation     = true
}

resource "aws_kms_alias" "sessions_actor" {
  name          = "alias/sessions-actor"
  target_key_id = aws_kms_key.sessions_actor.key_id
}

# See the following link for further information
# https://docs.aws.amazon.com/kms/latest/developerguide/key-policies.html
data "aws_iam_policy_document" "cloudwatch_kms" {
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
        "logs.eu-west-2.amazonaws.com",
        "events.amazonaws.com"
      ]
    }

    condition {
      test     = "StringEquals"
      variable = "aws:SourceAccount"
      values   = [data.aws_caller_identity.current.account_id]
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

data "aws_iam_policy_document" "event_receiver_kms" {
  statement {
    sid       = "Allow Encryption by Service"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Encrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
    ]

    principals {
      type = "Service"
      identifiers = [
        "events.amazonaws.com",
      ]
    }
  }

  statement {
    sid       = "Allow Decryption by Service"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey*",
      "kms:DescribeKey"
    ]

    principals {
      type = "Service"
      identifiers = [
        "sqs.amazonaws.com",
        "events.amazonaws.com"
      ]
    }
  }

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
}
