resource "aws_backup_vault" "primary" {
  name        = "${var.environment_name}_${data.aws_region.current.region}_dynamodb_vault"
  kms_key_arn = data.aws_kms_key.source_key.arn
}

resource "aws_backup_vault" "replica" {
  count       = var.region_replication_enabled ? 1 : 0
  name        = "${var.environment_name}_${var.replica_region}_dynamodb_vault"
  kms_key_arn = data.aws_kms_key.source_key_replica.arn
  region      = var.replica_region
}

resource "aws_backup_vault" "cross_account" {
  name        = "opg_use_an_lpa_${var.environment_name}_${data.aws_region.current.region}_backup"
  kms_key_arn = data.aws_kms_key.cross_account_key.arn
  provider    = aws.backup
}

resource "aws_backup_vault_policy" "cross_account" {
  provider          = aws.backup
  backup_vault_name = aws_backup_vault.cross_account.name
  policy            = data.aws_iam_policy_document.cross_account_permissions.json
}

data "aws_iam_policy_document" "cross_account_permissions" {
  provider = aws.backup
  statement {
    effect = "Allow"
    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"]
    }
    actions   = ["backup:CopyIntoBackupVault"]
    resources = [aws_backup_vault.cross_account.arn]
  }
}
