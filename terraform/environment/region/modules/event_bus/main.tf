resource "aws_cloudwatch_event_bus" "main" {
  count    = var.event_bus_enabled ? 1 : 0
  name     = var.environment_name
  provider = aws.region
}

resource "aws_cloudwatch_event_archive" "main" {
  count            = var.event_bus_enabled ? 1 : 0
  name             = var.environment_name
  event_source_arn = aws_cloudwatch_event_bus.main.arn
  provider         = aws.region
}
