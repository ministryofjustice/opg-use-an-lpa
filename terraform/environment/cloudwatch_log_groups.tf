resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${local.environment_name}_application_logs"
  retention_in_days = local.environment.log_retention_in_days
  kms_key_id        = data.aws_kms_alias.cloudwatch_encryption.target_key_arn
  tags = {
    "Name" = "${local.environment_name}_application_logs"
  }
}
