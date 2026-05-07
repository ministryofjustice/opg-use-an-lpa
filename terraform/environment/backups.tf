module "dynamodb_backup" {
  source = "./modules/dynamodb_backup"

  backups_enabled             = local.environment.dynamodb_backups.backups_enabled
  daily_backup_cold_storage   = local.environment.dynamodb_backups.daily_cold_storage_in_days
  daily_backup_deletion       = local.environment.dynamodb_backups.daily_backup_deletion_in_days
  environment_name            = local.environment_name
  key_alias                   = data.aws_kms_alias.backup_key_alias.name
  monthly_backup_cold_storage = local.environment.dynamodb_backups.monthly_cold_storage_in_days
  monthly_backup_deletion     = local.environment.dynamodb_backups.monthly_backup_deletion_in_days
  dynamodb_table_arns_to_backup = [
    aws_dynamodb_table.use_codes_table.arn,
    aws_dynamodb_table.use_users_table.arn,
    aws_dynamodb_table.viewer_codes_table.arn,
    aws_dynamodb_table.viewer_activity_table.arn,
    aws_dynamodb_table.user_lpa_actor_map.arn,
    aws_dynamodb_table.stats_table.arn,
  ]
}

data "aws_kms_alias" "backup_key_alias" {
  name = "alias/opg-use-an-lpa-${local.environment.account_name}-aws-backup-source-account-key"
}
