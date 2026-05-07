resource "aws_backup_vault" "main" {
  name        = "${var.environment_name}_${data.aws_region.current.region}_dynamodb_backup_vault"
  kms_key_arn = data.aws_kms_key.source_key.arn
}
