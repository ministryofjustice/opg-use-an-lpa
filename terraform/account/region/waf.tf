resource "aws_wafv2_web_acl" "main" {
  name        = "${var.account_name}-web-acl"
  description = "Managed rules"
  scope       = "REGIONAL"

  default_action {
    allow {}
  }

  rule {
    name     = "AWS-AWSManagedRulesAmazonIpReputationList"
    priority = 0

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesAmazonIpReputationList"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesAmazonIpReputationList"
      sampled_requests_enabled   = true
    }
  }

  rule {
    name     = "AWS-AWSManagedRulesPHPRuleSet"
    priority = 1

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesPHPRuleSet"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesPHPRuleSet"
      sampled_requests_enabled   = true
    }
  }
  rule {
    name     = "AWS-AWSManagedRulesKnownBadInputsRuleSet"
    priority = 2

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesKnownBadInputsRuleSet"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesKnownBadInputsRuleSet"
      sampled_requests_enabled   = true
    }
  }
  rule {
    name     = "AWS-AWSManagedRulesCommonRuleSet"
    priority = 3

    override_action {
      none {}
    }

    statement {

      managed_rule_group_statement {
        name        = "AWSManagedRulesCommonRuleSet"
        vendor_name = "AWS"

        rule_action_override {
          name = "NoUserAgent_HEADER"
          action_to_use {
            count {}
          }
        }


      }
    }
    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesCommonRuleSet"
      sampled_requests_enabled   = true
    }
  }

  rule {
    name     = "RateLimitSessionCheck"
    priority = 4

    action {
      block {}
    }

    visibility_config {
      sampled_requests_enabled   = true
      cloudwatch_metrics_enabled = true
      metric_name                = "RateLimitSessionCheck"
    }

    statement {
      rate_based_statement {
        limit              = 100
        aggregate_key_type = "CUSTOM_KEYS"
        scope_down_statement {
          byte_match_statement {
            field_to_match {
              uri_path {}
            }
            positional_constraint = "STARTS_WITH"
            search_string         = "/session-check"
            text_transformation {
              priority = 0
              type     = "NONE"
            }
          }
        }

        custom_key {
          header {
            name = "User-Agent"
            text_transformation {
              priority = 0
              type     = "NONE"
            }
          }
        }

        custom_key {
          ip {}
        }
      }
    }
  }

  rule {
    name     = "RateLimitByIP"
    priority = 5

    action {
      block {}
    }

    statement {
      rate_based_statement {
        limit              = 200
        aggregate_key_type = "IP"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      sampled_requests_enabled   = true
      metric_name                = "RateLimitByIP"
    }

  }

  visibility_config {
    cloudwatch_metrics_enabled = true
    metric_name                = "${var.account_name}-web-acl"
    sampled_requests_enabled   = true
  }

  provider = aws.region
}

resource "aws_wafv2_web_acl_logging_configuration" "main" {
  log_destination_configs = [aws_cloudwatch_log_group.waf_web_acl.arn]
  resource_arn            = aws_wafv2_web_acl.main.arn

  provider = aws.region
}

resource "aws_cloudwatch_log_group" "waf_web_acl" {
  name              = "aws-waf-logs-${var.account_name}"
  retention_in_days = 120
  kms_key_id        = aws_kms_key.waf_cloudwatch_log_encryption.arn
  tags = {
    "Name" = "${var.account_name}-web-acl"
  }

  provider = aws.region
}

resource "aws_kms_key" "waf_cloudwatch_log_encryption" {
  description             = "AWS WAF Cloudwatch encryption ${var.account_name}"
  deletion_window_in_days = 10
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.waf_cloudwatch_log_encryption_kms.json

  provider = aws.region
}

resource "aws_kms_alias" "waf_cloudwatch_log_encryption" {
  name          = "alias/waf_cloudwatch_log_encryption"
  target_key_id = aws_kms_key.waf_cloudwatch_log_encryption.key_id

  provider = aws.region
}

# See the following link for further information
# https://docs.aws.amazon.com/kms/latest/developerguide/key-policies.html
data "aws_iam_policy_document" "waf_cloudwatch_log_encryption_kms" {
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
