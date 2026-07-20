data "aws_caller_identity" "current" {}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}

data "aws_region" "current" {}

data "aws_iam_policy" "default_boundary" {
  count = local.account.permissions_boundary_enabled ? 1 : 0
  name  = "opg-use-an-lpa-non-ci-boundary"
}
