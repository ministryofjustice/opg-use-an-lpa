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

variable "enable_vault_lock" {
  type        = bool
  description = "Whether to enable vault lock on the backup vaults"
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

variable "vault_lock_min_retention_days" {
  type        = number
  description = "The minimum number of days to retain backups in the vault when vault lock is enabled"

  validation {
    condition = !var.enable_vault_lock || (var.vault_lock_min_retention_days > 0 &&
    floor(var.vault_lock_min_retention_days) == var.vault_lock_min_retention_days)
    error_message = "When enable_vault_lock is true, vault_lock_min_retention_days must be a positive whole number."
  }

  validation {
    condition = !var.enable_vault_lock || (var.vault_lock_min_retention_days <= var.daily_backup_deletion &&
    var.vault_lock_min_retention_days <= var.monthly_backup_deletion)
    error_message = "When enable_vault_lock is true, vault_lock_min_retention_days must not exceed daily_backup_deletion or monthly_backup_deletion, otherwise backup jobs would request a retention below the vault floor and fail."
  }
}

variable "vault_lock_max_retention_days" {
  type        = number
  description = "The maximum number of days to retain backups in the vault when vault lock is enabled"

  validation {
    condition = !var.enable_vault_lock || (var.vault_lock_max_retention_days > 0 &&
    floor(var.vault_lock_max_retention_days) == var.vault_lock_max_retention_days)
    error_message = "When enable_vault_lock is true, vault_lock_max_retention_days must be a positive whole number."
  }

  validation {
    condition     = !var.enable_vault_lock || var.vault_lock_max_retention_days >= var.vault_lock_min_retention_days
    error_message = "When enable_vault_lock is true, vault_lock_max_retention_days must be greater than or equal to vault_lock_min_retention_days."
  }

  validation {
    condition = !var.enable_vault_lock || (var.vault_lock_max_retention_days >= var.daily_backup_deletion &&
    var.vault_lock_max_retention_days >= var.monthly_backup_deletion)
    error_message = "When enable_vault_lock is true, vault_lock_max_retention_days must be at least as large as daily_backup_deletion and monthly_backup_deletion, otherwise backup jobs would request a retention above the vault ceiling and fail."
  }
}
