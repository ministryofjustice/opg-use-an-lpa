data "aws_caller_identity" "current" {}

data "aws_region" "current" {}

data "aws_kms_key" "source_key" {
  key_id = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:${var.key_alias}"
}
