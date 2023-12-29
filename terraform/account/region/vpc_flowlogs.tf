resource "aws_flow_log" "vpc_flow_logs" {
  iam_role_arn    = var.vpc_flow_logs_iam_role.arn
  log_destination = aws_cloudwatch_log_group.vpc_flow_logs.arn
  traffic_type    = "ALL"
  vpc_id          = aws_default_vpc.default.id

  provider = aws.region
}

resource "aws_cloudwatch_log_group" "vpc_flow_logs" {
  name              = "vpc_flow_logs-${data.aws_region.current.name}"
  retention_in_days = 400
  kms_key_id        = data.aws_kms_alias.cloudwatch_mrk.arn

  provider = aws.region
}

# Kept around to avoid losing logs after switching to region-specific flow logs group.
# This can be deleted 400 days after the creation of aws_cloudwatch_log_group.vpc_flow_logs.
resource "aws_cloudwatch_log_group" "old_vpc_flow_logs" {
  count             = data.aws_region.current.name == "eu-west-1" ? 1 : 0
  name              = "vpc_flow_logs"
  retention_in_days = 400
  kms_key_id        = data.aws_kms_alias.cloudwatch_mrk.arn

  provider = aws.region
}
