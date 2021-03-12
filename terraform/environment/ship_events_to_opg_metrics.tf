locals {
  clsf_event_codes = [
    "ACCOUNT_CREATED",
    "ACCOUNT_DELETED",
  ]
}
resource "aws_cloudwatch_log_subscription_filter" "events" {
  for_each        = toset(local.clsf_event_codes)
  name            = "${local.environment}_clsf_to_sqs_${lower(each.value)}"
  log_group_name  = aws_cloudwatch_log_group.application_logs.name
  filter_pattern  = "{$.context.event_code = ${each.value}}"
  destination_arn = data.aws_lambda_function.clsf_to_sqs.arn
  depends_on      = [aws_lambda_permission.allow_cloudwatch]
}
resource "aws_lambda_permission" "allow_cloudwatch" {
  statement_id  = "AllowExecutionFromCloudWatch"
  action        = "lambda:InvokeFunction"
  function_name = data.aws_lambda_function.clsf_to_sqs.function_name
  principal     = "logs.eu-west-1.amazonaws.com"
  source_arn    = "${aws_cloudwatch_log_group.application_logs.arn}:*"
}
