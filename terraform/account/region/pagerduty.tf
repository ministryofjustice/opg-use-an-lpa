resource "aws_kms_key" "pagerduty_sns" {
  description             = "KMS Key for encryption of AWS Config SNS Messages"
  deletion_window_in_days = 10
  policy                  = data.aws_iam_policy_document.pagerduty_sns_kms.json
  enable_key_rotation     = true

  provider = aws.region
}

resource "aws_kms_alias" "pagerduty_sns" {
  name          = "alias/pagerduty-sns"
  target_key_id = aws_kms_key.pagerduty_sns.key_id

  provider = aws.region
}


data "aws_iam_policy_document" "pagerduty_sns_kms" {
  statement {
    sid       = "Allow Key to be used for Encryption by AWS Cloudwatch"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey*",
    ]
    principals {
      identifiers = ["cloudwatch.amazonaws.com"]
      type        = "Service"
    }
    condition {
      test     = "StringEquals"
      variable = "aws:SourceAccount"
      values   = [data.aws_caller_identity.current.account_id]
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

data "pagerduty_vendor" "cloudwatch" {
  name = "Cloudwatch"
}

resource "pagerduty_service_integration" "cloudwatch_integration" {
  name    = "${data.pagerduty_vendor.cloudwatch.name} ${var.environment_name} Account"
  service = var.account.pagerduty_service_id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "aws_sns_topic" "cloudwatch_to_pagerduty" {
  name              = "CloudWatch-to-PagerDuty-${var.environment_name}-Account"
  kms_master_key_id = aws_kms_key.pagerduty_sns.key_id

  provider = aws.region
}

resource "aws_sns_topic_subscription" "cloudwatch_sns_subscription" {
  topic_arn              = aws_sns_topic.cloudwatch_to_pagerduty.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_integration.integration_key}/enqueue"

  provider = aws.region
}

resource "pagerduty_service_integration" "cloudwatch_application_insights" {
  count   = var.account.cloudwatch_application_insights_enabled ? 1 : 0
  name    = "Use an LPA ${data.aws_region.current.name} Cloudwatch Application Insights Ops Item Alarm"
  service = var.account.pagerduty_service_id
  vendor  = data.pagerduty_vendor.cloudwatch.id
}

resource "aws_sns_topic_subscription" "cloudwatch_application_insights" {
  count                  = var.account.cloudwatch_application_insights_enabled ? 1 : 0
  topic_arn              = aws_sns_topic.cloudwatch_application_insights.arn
  protocol               = "https"
  endpoint_auto_confirms = true
  endpoint               = "https://events.pagerduty.com/integration/${pagerduty_service_integration.cloudwatch_application_insights[0].integration_key}/enqueue"
  provider               = aws.region
}
