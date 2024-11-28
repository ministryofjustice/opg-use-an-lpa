variable "environment_name" {
  description = "The name of the environment"
  type        = string
}

variable "event_bus_enabled" {
  description = "Whether to enable Event Bus"
  type        = bool
  default     = false
}
