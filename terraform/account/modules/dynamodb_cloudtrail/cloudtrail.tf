resource "aws_cloudtrail" "cloudtrail" {
  name                          = "ddb-cloudtrail-${data.aws_region.current.name}-${var.trail_name_suffix}"
  cloud_watch_logs_group_arn    = "${aws_cloudwatch_log_group.cloudtrail.arn}:*"
  cloud_watch_logs_role_arn     = aws_iam_role.cloudtrail.arn
  enable_log_file_validation    = true
  include_global_service_events = true
  is_multi_region_trail         = false
  kms_key_id                    = aws_kms_key.cloudtrail_s3.arn
  s3_bucket_name                = aws_s3_bucket.cloudtrail.bucket

  event_selector {
    include_management_events = true
    read_write_type           = "All"

    data_resource {
      type   = "AWS::DynamoDB::Table"
      values = ["arn:aws:dynamodb"]
    }
  }
}

resource "aws_cloudwatch_log_group" "cloudtrail" {
  name       = "ddb-cloudtrail-${data.aws_region.current.name}-${var.trail_name_suffix}"
  kms_key_id = aws_kms_key.cloudtrail_log_group_key.key_id
}

resource "aws_iam_role" "cloudtrail" {
  name               = "ddb-cloudtrail-${data.aws_region.current.name}-${var.trail_name_suffix}"
  assume_role_policy = data.aws_iam_policy_document.cloudtrail_role_assume_role_policy.json
}

data "aws_iam_policy_document" "cloudtrail_role_assume_role_policy" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["cloudtrail.amazonaws.com"]
    }
  }
}

resource "aws_iam_role_policy" "cloudtrail" {
  name   = "ddb-cloudtrail-${data.aws_region.current.name}-${var.trail_name_suffix}"
  role   = aws_iam_role.cloudtrail.id
  policy = data.aws_iam_policy_document.cloudtrail_role_policy.json
}

data "aws_iam_policy_document" "cloudtrail_role_policy" {
  statement {
    actions = [
      "logs:CreateLogGroup",
      "logs:CreateLogStream",
      "logs:PutLogEvents",
      "logs:DescribeLogGroups",
      "logs:DescribeLogStreams"
    ]

    resources = ["*"]
    effect    = "Allow"
  }
}
