resource "aws_backup_vault" "main" {
  name = "${local.environment}_main_backup_vault"
  tags = local.default_tags
}

resource "aws_iam_role" "aws_backup_role" {
  name               = "aws_backup_role"
  assume_role_policy = data.aws_iam_policy_document.aws_backup_assume_policy.json
  tags               = local.default_tags
}

data "aws_iam_policy_document" "aws_backup_assume_policy" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["backup.amazonaws.com"]
      type        = "Service"
    }
  }
}

resource "aws_iam_role_policy_attachment" "aws_backup_role" {
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
  role       = aws_iam_role.aws_backup_role.name
}
