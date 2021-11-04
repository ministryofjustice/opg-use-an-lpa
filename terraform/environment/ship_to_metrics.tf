data "aws_sqs_queue" "ship_to_opg_metrics" {
  count = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  name  = "${local.account_name}-ship-to-opg-metrics"
}
data "aws_lambda_function" "clsf_to_sqs" {
  count         = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  function_name = "clsf-to-sqs"
}

data "aws_lambda_function" "ship_to_opg_metrics" {
  count         = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  function_name = "ship-to-opg-metrics"
}

resource "aws_cloudwatch_log_subscription_filter" "events" {
  count           = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  name            = "${local.environment}-clsf-to-sqs}"
  log_group_name  = aws_cloudwatch_log_group.application_logs.name
  filter_pattern  = "{$.context.event_code EXISTS}"
  destination_arn = data.aws_lambda_function.clsf_to_sqs[0].arn
  depends_on      = [aws_lambda_permission.allow_cloudwatch]
}

resource "aws_lambda_permission" "allow_cloudwatch" {
  count         = local.account.ship_metrics_queue_enabled == true ? 1 : 0
  statement_id  = "${local.environment}-AllowExecutionFromCloudWatch"
  action        = "lambda:InvokeFunction"
  function_name = data.aws_lambda_function.clsf_to_sqs[0].function_name
  principal     = "logs.eu-west-1.amazonaws.com"
  source_arn    = "${aws_cloudwatch_log_group.application_logs.arn}:*"
}
