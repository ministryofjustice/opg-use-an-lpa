resource "aws_flow_log" "vpc_flow_logs" {
  iam_role_arn    = aws_iam_role.vpc_flow_logs.arn
  log_destination = aws_cloudwatch_log_group.vpc_flow_logs.arn
  traffic_type    = "ALL"
  vpc_id          = aws_default_vpc.default.id

  provider = aws.region
}

resource "aws_cloudwatch_log_group" "vpc_flow_logs" {
  name              = "vpc_flow_logs"
  retention_in_days = 400
  kms_key_id        = data.aws_kms_alias.cloudwatch_mrk.arn

  provider = aws.region
}

resource "aws_iam_role" "vpc_flow_logs" {
  name               = "vpc_flow_logs"
  assume_role_policy = data.aws_iam_policy_document.vpc_flow_logs_role_assume_role_policy.json

  provider = aws.region
}

data "aws_iam_policy_document" "vpc_flow_logs_role_assume_role_policy" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["vpc-flow-logs.amazonaws.com"]
    }
  }
}

resource "aws_iam_role_policy" "vpc_flow_logs" {
  name   = "vpc_flow_logs"
  role   = aws_iam_role.vpc_flow_logs.id
  policy = data.aws_iam_policy_document.vpc_flow_logs_role_policy.json

  provider = aws.region
}

data "aws_iam_policy_document" "vpc_flow_logs_role_policy" {
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