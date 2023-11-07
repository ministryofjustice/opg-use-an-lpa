resource "aws_cloudwatch_log_group" "application_logs" {
  name              = "${var.environment_name}_application_logs"
  retention_in_days = var.log_retention_days
  kms_key_id        = data.aws_kms_alias.cloudwatch_encryption.target_key_arn
  tags = {
    "Name" = "${var.environment_name}_application_logs"
  }

  provider = aws.region
}

resource "aws_cloudwatch_query_definition" "app_container_messages" {
  name            = "Application Logs/${var.environment_name} app container messages"
  log_group_names = [aws_cloudwatch_log_group.application_logs.name]

  query_string = <<EOF
fields @timestamp, coalesce(message, request) as logged
| parse @logStream ".*-app" as service
| display @timestamp, service, logged
| filter @message not like /(Identity of incoming request|ELB-HealthChecker|\/session-check|\/session-refresh|GET \/healthcheck|GET - -)/
| filter @logStream like /-app\./
| sort @timestamp desc
EOF

  provider = aws.region
}
