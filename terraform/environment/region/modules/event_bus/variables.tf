variable "environment_name" {
  description = "The name of the environment"
  type        = string
}

variable "event_bus_enabled" {
  description = "Whether to enable Event Bus"
  type        = bool
  default     = false
}

variable "current_region" {
  description = "The current region"
  type        = string
}

variable "receive_account_ids" {
  description = "The account ids that can send events to the event bus"
  type        = list(string)
}

variable "queue_visibility_timeout" {
  description = "The visibility timeout for the SQS queue"
  type        = number
}
