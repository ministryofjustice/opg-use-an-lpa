variable "kms_key_arn" {
  description = "The ARN of the KMS key to use for encryption of the CloudWatch Logs."
  type        = string
}

variable "domains_allowed" {
  description = "The domains allowed to be resolved by the DNS server."
  type        = list(string)
}

variable "domains_blocked" {
  description = "The domains blocked to be resolved by the DNS server."
  type        = list(string)
}

variable "enable_block" {
  description = "Block all domains in the domains_blocked list. If false, alerts will be generated for blocked domains but they will not be blocked."
  type        = bool
  default     = false
}

variable "brute_force_cache_primary_endpoint_address" {
  description = "The primary endpoint address of the Brute Force Cache."
  type        = string
}
