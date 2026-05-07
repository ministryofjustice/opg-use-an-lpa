resource "aws_backup_vault" "primary" {
  name        = "${var.environment_name}_${data.aws_region.current.region}_dynamodb_vault"
  kms_key_arn = data.aws_kms_key.source_key.arn
}

resource "aws_backup_vault" "replica" {
  name        = "${var.environment_name}_${data.aws_region.current.region}_dynamodb_vault_replica"
  kms_key_arn = data.aws_kms_key.source_key.arn
  provider    = aws.eu_west_2
}
