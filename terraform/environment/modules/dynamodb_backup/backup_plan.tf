resource "aws_backup_plan" "main" {
  count = var.backups_enabled ? 1 : 0
  name  = "${var.environment_name}_backup_plan"

  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(0 5 ? * * *)"
    start_window        = 480
    target_vault_name   = aws_backup_vault.main.name

    lifecycle {
      cold_storage_after = var.daily_backup_cold_storage
      delete_after       = var.daily_backup_deletion
    }
  }
  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "Monthly"
    schedule            = "cron(0 5 1 * ? *)"
    start_window        = 480
    target_vault_name   = aws_backup_vault.main.name

    lifecycle {
      cold_storage_after = var.monthly_backup_cold_storage
      delete_after       = var.monthly_backup_deletion
    }
  }
}

resource "aws_backup_selection" "main" {
  count        = var.backups_enabled ? 1 : 0
  iam_role_arn = data.aws_iam_role.dynamodb_backup_role.arn
  name         = "${var.environment_name}_backup_selection"
  plan_id      = aws_backup_plan.main[0].id

  resources = var.dynamodb_table_arns_to_backup
}
