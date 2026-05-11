data "aws_caller_identity" "current" {}

data "aws_region" "current" {}

data "aws_kms_key" "source_key" {
  key_id = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:${var.key_alias}"
}

data "aws_kms_key" "source_key_replica" {
  key_id = "arn:aws:kms:${var.replica_region}:${data.aws_caller_identity.current.account_id}:${var.key_alias}"
  region = var.replica_region
}
