output "receive_events_sqs_queue_name" {
  description = "The name of the SQS queue created by the event_bus module."
  value       = aws_sqs_queue.receive_events_queue[*].name
}

output "receive_events_sqs_queue_arn" {
  description = "The name of the SQS queue created by the event_bus module."
  value       = aws_sqs_queue.receive_events_queue[*].arn
}

output "receive_events_bus_arn" {
  description = "The ARN of the event bus created by the event_bus module."
  value       = aws_cloudwatch_event_bus.main[*].arn
}
