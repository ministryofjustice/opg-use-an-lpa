module "dynamodb_backup" {
  count  = local.environment.dynamodb_backups.backups_enabled ? 1 : 0
  source = "./modules/dynamodb_backup"

  account_name                  = local.environment.account_name
  cross_account_backup_enabled  = local.environment.dynamodb_backups.cross_account_backup_enabled
  daily_backup_cold_storage     = local.environment.dynamodb_backups.daily_cold_storage_in_days
  daily_backup_deletion         = local.environment.dynamodb_backups.daily_backup_deletion_in_days
  enable_vault_lock             = local.environment.dynamodb_backups.enable_vault_lock
  environment_name              = local.environment_name
  key_alias                     = data.aws_kms_alias.backup_key_alias.name
  monthly_backup_cold_storage   = local.environment.dynamodb_backups.monthly_cold_storage_in_days
  monthly_backup_deletion       = local.environment.dynamodb_backups.monthly_backup_deletion_in_days
  region_replication_enabled    = local.environment.dynamodb_backups.region_replication_enabled
  vault_lock_min_retention_days = local.environment.dynamodb_backups.vault_lock_min_retention_days
  vault_lock_max_retention_days = local.environment.dynamodb_backups.vault_lock_max_retention_days
  dynamodb_table_arns_to_backup = [
    aws_dynamodb_table.use_codes_table.arn,
    aws_dynamodb_table.use_users_table.arn,
    aws_dynamodb_table.viewer_codes_table.arn,
    aws_dynamodb_table.viewer_activity_table.arn,
    aws_dynamodb_table.user_lpa_actor_map.arn,
    aws_dynamodb_table.stats_table.arn,
  ]
  providers = {
    aws        = aws.eu_west_1
    aws.backup = aws.backup
  }
}

data "aws_kms_alias" "backup_key_alias" {
  name = "alias/opg-use-an-lpa-${local.environment.account_name}-aws-backup-source-account-key"
}
