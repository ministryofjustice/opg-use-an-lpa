variable "environment_name" {
  description = "The name of the environment"
  type        = string
}

variable "account_name" {
  description = "The name of the account"
  type        = string
}

variable "event_bus_enabled" {
  description = "Whether to enable Event Bus"
  type        = bool
  default     = false
}

/*
variable "ingress_lambda_name" {
  description = "The name of the ingress lambda"
  type        = string
}
*/

variable "current_region" {
  description = "The current region"
  type        = string
}
