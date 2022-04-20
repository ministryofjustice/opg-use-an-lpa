resource "aws_cloudtrail" "cloudtrail" {
  name                          = "ddb-cloudtrail-${data.aws_region.current.name}-${var.trail_name_suffix}"
  cloud_watch_logs_group_arn    = "${aws_cloudwatch_log_group.cloudtrail.arn}:*"
  cloud_watch_logs_role_arn     = aws_iam_role.cloudtrail.arn
  enable_log_file_validation    = true
  include_global_service_events = true
  is_multi_region_trail         = true
  kms_key_id                    = aws_kms_key.cloudtrail_s3.arn
  s3_bucket_name                = aws_s3_bucket.cloudtrail.bucket

  event_selector {
    include_management_events = false
    read_write_type           = "All"

    data_resource {
      type   = "AWS::DynamoDB::Table"
      values = ["arn:aws:dynamodb"]
    }
  }
}

resource "aws_cloudwatch_log_group" "cloudtrail" {
  name              = "ddb-cloudtrail-${data.aws_region.current.name}-${var.trail_name_suffix}"
  retention_in_days = 90
  kms_key_id        = aws_kms_alias.cloudtrail_log_group_key.target_key_arn
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
      "logs:CreateLogStream",
      "logs:CreateLogGroup",
      "logs:PutLogEvents",
      "logs:DescribeLogGroups",
      "logs:DescribeLogStreams"
    ]

    resources = [
      aws_cloudtrail.cloudtrail.arn,
      aws_cloudwatch_log_group.cloudtrail.arn
    ]
    effect = "Allow"
  }
}

resource "aws_cloudwatch_query_definition" "dns_firewall_statistics" {
  name            = "Cloudtrail/DynamoDB Data Plane Events"
  log_group_names = [aws_cloudwatch_log_group.cloudtrail.name]
  query_string    = <<EOF
fields eventTime, eventName, requestParameters.tableName as table, requestParameters.indexName as index, userIdentity.sessionContext.sessionIssuer.userName as iam_user
| sort eventTime desc
EOF
}
