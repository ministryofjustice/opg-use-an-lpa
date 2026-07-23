resource "aws_ebs_snapshot_block_public_access" "this" {
  state = "block-all-sharing"

  provider = aws.region
}

resource "aws_ssm_service_setting" "public_sharing_permission" {
  setting_id    = "arn:aws:ssm:${data.aws_region.current.region}:${var.account.account_id}:servicesetting/ssm/documents/console/public-sharing-permission"
  setting_value = "Disable"

  provider = aws.region
}
