variable "environment_name" {
  description = "The name of the environment"
  type        = string
}

variable "event_bus_enabled" {
  description = "Whether to enable Event Bus"
  type        = bool
  default     = false
}

variable "lambda_function_name" {
  description = "The name of the ingress lambda"
  type        = string
}

variable "current_region" {
  description = "The current region"
  type        = string
}

variable "receive_account_ids" {
  description = "The account ids that can send events to the event bus"
  type        = list(string)
}
