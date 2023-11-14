resource "aws_flow_log" "vpc_flow_logs" {
  iam_role_arn    = var.vpc_flow_logs_iam_role.arn
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
