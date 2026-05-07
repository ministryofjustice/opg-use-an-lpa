data "aws_caller_identity" "current" {}

data "aws_region" "current" {}

data "aws_region" "eu_west_2" {
  provider = aws.eu_west_2
}

data "aws_kms_key" "source_key" {
  key_id = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:${var.key_alias}"
}

data "aws_kms_key" "source_key_replica" {
  key_id   = "arn:aws:kms:${data.aws_region.eu_west_2.region}:${data.aws_caller_identity.current.account_id}:${var.key_alias}"
  provider = aws.eu_west_2
}
