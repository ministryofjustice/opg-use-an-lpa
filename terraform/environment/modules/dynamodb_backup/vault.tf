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
  count       = var.cross_account_backup_enabled ? 1 : 0
  name        = "opg_use_an_lpa_${var.environment_name}_${data.aws_region.current.region}_backup"
  kms_key_arn = data.aws_kms_key.cross_account_key.arn
}
