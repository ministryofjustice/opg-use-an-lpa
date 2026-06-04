variable "account_name" {
  type        = string
  description = "AWS account name to use in resource names"
}

variable "environment_name" {
  type        = string
  description = "Environment name to use in resource names"
}

variable "daily_backup_deletion" {
  type        = number
  description = "Number of days to retain daily backups before deletion"
}

variable "monthly_backup_deletion" {
  type        = number
  description = "Number of days to retain monthly backups before deletion"
}
variable "daily_backup_cold_storage" {
  type        = number
  description = "Number of days to retain daily backups in cold storage"
}
variable "monthly_backup_cold_storage" {
  type        = number
  description = "Number of days to retain monthly backups in cold storage"
}

variable "key_alias" {
  type        = string
  description = "The alias for the KMS key used to encrypt DynamoDB backups in the source account"
}

variable "dynamodb_table_arns_to_backup" {
  type        = set(string)
  default     = []
  description = "The ARNs of the DynamoDB tables to backup"
}

variable "region_replication_enabled" {
  type        = bool
  description = "Whether to replicate backups to a secondary region"
}

variable "replica_region" {
  type        = string
  description = "The region where backups will be replicated to"
  default     = "eu-west-2"
}

variable "cross_account_backup_enabled" {
  type        = bool
  description = "Whether to enable cross-account backup replication"
}
