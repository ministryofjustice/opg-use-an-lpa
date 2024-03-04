resource "aws_backup_vault" "main" {
  name = "${local.environment}_main_backup_vault"

  provider = aws.eu_west_1
}

resource "aws_iam_role" "aws_backup_role" {
  name               = "aws_backup_role"
  assume_role_policy = data.aws_iam_policy_document.aws_backup_assume_policy.json

  provider = aws.eu_west_1
}

data "aws_iam_policy_document" "aws_backup_assume_policy" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["backup.amazonaws.com"]
      type        = "Service"
    }

    condition {
      test     = "StringEquals"
      variable = "aws:SourceAccount"
      values   = [data.aws_caller_identity.current.account_id]
    }
  }
}

resource "aws_iam_role_policy_attachment" "aws_backup_role" {
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
  role       = aws_iam_role.aws_backup_role.name

  provider = aws.eu_west_1
}
