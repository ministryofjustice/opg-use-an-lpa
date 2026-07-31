data "aws_caller_identity" "current" {}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}

data "aws_region" "current" {}

data "aws_iam_policy" "default_boundary" {
  name = "opg-use-an-lpa-non-ci-boundary"
}
