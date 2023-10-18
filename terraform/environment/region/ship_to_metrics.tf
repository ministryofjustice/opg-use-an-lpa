data "aws_sqs_queue" "ship_to_opg_metrics" {
  count = var.ship_metrics_queue_enabled ? 1 : 0
  name  = "${var.account_name}-ship-to-opg-metrics"
}

data "aws_lambda_function" "clsf_to_sqs" {
  count         = var.ship_metrics_queue_enabled ? 1 : 0
  function_name = "clsf-to-sqs"
}

data "aws_lambda_function" "ship_to_opg_metrics" {
  count         = var.ship_metrics_queue_enabled ? 1 : 0
  function_name = "ship-to-opg-metrics"
}

resource "aws_cloudwatch_log_subscription_filter" "events" {
  count           = var.ship_metrics_queue_enabled ? 1 : 0
  name            = "${var.environment_name}-clsf-to-sqs"
  log_group_name  = aws_cloudwatch_log_group.application_logs.name
  filter_pattern  = "{ $.context.event_code = * }"
  destination_arn = data.aws_lambda_function.clsf_to_sqs[0].arn
  depends_on      = [aws_lambda_permission.allow_cloudwatch]
}

resource "aws_lambda_permission" "allow_cloudwatch" {
  count         = var.ship_metrics_queue_enabled ? 1 : 0
  statement_id  = "${var.environment_name}-AllowExecutionFromCloudWatch"
  action        = "lambda:InvokeFunction"
  function_name = data.aws_lambda_function.clsf_to_sqs[0].function_name
  principal     = "logs.${data.aws_region.current.name}.amazonaws.com"
  source_arn    = "${aws_cloudwatch_log_group.application_logs.arn}:*"
}
