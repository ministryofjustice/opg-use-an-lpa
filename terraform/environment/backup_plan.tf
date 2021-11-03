resource "aws_backup_plan" "main" {
  count = local.account.have_a_backup_plan == true ? 1 : 0
  name  = "${local.environment}_main_backup_plan"

  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(0 5 ? * * *)"
    start_window        = 480
    target_vault_name   = data.aws_backup_vault.main.name

    lifecycle {
      cold_storage_after = 0
      delete_after       = 90
    }
  }
  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "Monthly"
    schedule            = "cron(0 5 1 * ? *)"
    start_window        = 480
    target_vault_name   = data.aws_backup_vault.main.name

    lifecycle {
      cold_storage_after = 30
      delete_after       = 365
    }
  }
}

data "aws_backup_vault" "main" {
  name = "${local.account.account_name}_main_backup_vault"
}

data "aws_iam_role" "aws_backup_role" {
  name = "aws_backup_role"
}

resource "aws_backup_selection" "main" {
  count        = local.account.have_a_backup_plan == true ? 1 : 0
  iam_role_arn = data.aws_iam_role.aws_backup_role.arn
  name         = "${local.environment}_main_backup_selection"
  plan_id      = aws_backup_plan.main[0].id

  resources = [
    aws_dynamodb_table.actor_codes_table.arn,
    aws_dynamodb_table.actor_users_table.arn,
    aws_dynamodb_table.viewer_codes_table.arn,
    aws_dynamodb_table.viewer_activity_table.arn,
    aws_dynamodb_table.user_lpa_actor_map.arn,
  ]
}
