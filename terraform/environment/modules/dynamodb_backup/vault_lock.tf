resource "aws_backup_vault_lock_configuration" "primary" {
  count              = var.enable_vault_lock ? 1 : 0
  backup_vault_name  = aws_backup_vault.primary.name
  min_retention_days = var.vault_lock_min_retention_days
}

resource "aws_backup_vault_lock_configuration" "replica" {
  count              = var.enable_vault_lock && var.region_replication_enabled ? 1 : 0
  backup_vault_name  = aws_backup_vault.replica[0].name
  min_retention_days = var.vault_lock_min_retention_days
  region             = var.replica_region
}
