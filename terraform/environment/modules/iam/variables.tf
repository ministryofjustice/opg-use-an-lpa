variable "environment_name" {
  description = "The name of the environment"
  type        = string
}

variable "default_boundary" {
  description = "Default permissions boundary for non-ci roles"
  type        = string
}
