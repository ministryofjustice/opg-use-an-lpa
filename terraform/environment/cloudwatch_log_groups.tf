resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${local.environment_name}_application_logs"
  retention_in_days = local.environment.log_retention_in_days

  tags = {
    "Name" = "${local.environment_name}_application_logs"
  }
}
