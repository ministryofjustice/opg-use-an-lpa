resource "aws_iam_role" "use_cross_account_backup_role" {
  provider           = aws.backup
  name               = "${local.account_name}-use-cross-account-backup-role"
  assume_role_policy = data.aws_iam_policy_document.use-cross-account-backup-role-permissions.json
}

resource "aws_iam_role_policy_attachment" "use_cross_account_backup_role" {
  provider   = aws.backup
  role       = aws_iam_role.use_cross_account_backup_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
}

data "aws_iam_policy_document" "use-cross-account-backup-role-permissions" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["backup.amazonaws.com"]
    }
  }
}
