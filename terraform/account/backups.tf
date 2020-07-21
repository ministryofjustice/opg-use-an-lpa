resource "aws_backup_plan" "main" {
  name = "${local.environment}_main_backup_plan"

  rule {
    completion_window   = 10080
    recovery_point_tags = {}
    rule_name           = "DailyBackups"
    schedule            = "cron(0 5 ? * * *)"
    start_window        = 480
    target_vault_name   = aws_backup_vault.main.name

    lifecycle {
      cold_storage_after = 0
      delete_after       = 35
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
      cold_storage_after = 30
      delete_after       = 365
    }
  }
  tags = local.default_tags
}
resource "aws_backup_vault" "main" {
  name = "${local.environment}_main_backup_vault"
}

# resource "aws_iam_role" "example" {
#   name               = "example"
#   assume_role_policy = <<POLICY
# {
#   "Version": "2012-10-17",
#   "Statement": [
#     {
#       "Action": ["sts:AssumeRole"],
#       "Effect": "allow",
#       "Principal": {
#         "Service": ["backup.amazonaws.com"]
#       }
#     }
#   ]
# }
# POLICY
# }

# resource "aws_iam_role_policy_attachment" "example" {
#   policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
#   role       = "${aws_iam_role.example.name}"
# }

# resource "aws_backup_selection" "example" {
#   iam_role_arn = "${aws_iam_role.example.arn}"
#   name         = "tf_example_backup_selection"
#   plan_id      = "${aws_backup_plan.example.id}"

#   resources = [
#     "${aws_db_instance.example.arn}",
#     "${aws_ebs_volume.example.arn}",
#     "${aws_efs_file_system.example.arn}",
#   ]
# }
