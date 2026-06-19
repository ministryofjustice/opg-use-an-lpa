resource "aws_backup_plan" "main" {
  name = "${var.environment_name}_backup_plan"

  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(0 5 ? * * *)"
    start_window        = 480
    target_vault_name   = aws_backup_vault.primary.name

    lifecycle {
      cold_storage_after = var.daily_backup_cold_storage
      delete_after       = var.daily_backup_deletion
    }

    copy_action {
      destination_vault_arn = aws_backup_vault.replica[0].arn
      lifecycle {
        cold_storage_after = var.daily_backup_cold_storage
        delete_after       = var.daily_backup_deletion
      }
    }

    dynamic "copy_action" {
      for_each = var.cross_account_backup_enabled ? [1] : []
      content {
        destination_vault_arn = aws_backup_vault.cross_account.arn
        lifecycle {
          cold_storage_after = var.daily_backup_cold_storage
          delete_after       = var.daily_backup_deletion
        }
      }
    }
  }
  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "Monthly"
    schedule            = "cron(0 5 1 * ? *)"
    start_window        = 480
    target_vault_name   = aws_backup_vault.primary.name

    lifecycle {
      cold_storage_after = var.monthly_backup_cold_storage
      delete_after       = var.monthly_backup_deletion
    }

    copy_action {
      destination_vault_arn = aws_backup_vault.replica[0].arn
      lifecycle {
        cold_storage_after = var.monthly_backup_cold_storage
        delete_after       = var.monthly_backup_deletion
      }
    }
    dynamic "copy_action" {
      for_each = var.cross_account_backup_enabled ? [1] : []
      content {
        destination_vault_arn = aws_backup_vault.cross_account.arn
        lifecycle {
          cold_storage_after = var.monthly_backup_cold_storage
          delete_after       = var.monthly_backup_deletion
        }
      }
    }
  }
}

resource "aws_backup_selection" "main" {
  iam_role_arn = data.aws_iam_role.dynamodb_backup_role.arn
  name         = "${var.environment_name}_backup_selection"
  plan_id      = aws_backup_plan.main.id
  resources    = var.dynamodb_table_arns_to_backup
}
