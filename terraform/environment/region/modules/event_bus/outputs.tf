output "receive_events_sqs_queue_name" {
  description = "The name of the SQS queue created by the event_bus module."
  value       = aws_sqs_queue.receive_events_queue[*].name
}
