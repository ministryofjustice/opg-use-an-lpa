variable "key_description" {
  type        = string
  description = "Description of the KMS key"
}

variable "deletion_window_in_days" {
  type        = number
  description = "Duration in days after which the key is deleted permanently"
  default     = 30
}

variable "enable_key_rotation" {
  type        = bool
  description = "Enable automatic key rotation"
  default     = true
}

variable "key_alias" {
  type        = string
  description = "Alias for the KMS key"
}

variable "key_policy" {
  type        = string
  description = "IAM Policy for the KMS key"
  default     = null
}
