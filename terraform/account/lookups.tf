data "aws_caller_identity" "current" {}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}

data "aws_region" "current" {}

data "aws_iam_policy" "default_boundary" {
  count = local.account_name == "development" ? 1 : 0
  name  = "opg-use-an-lpa-non-ci-boundary"
}
