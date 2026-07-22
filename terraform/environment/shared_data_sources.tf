data "aws_kms_alias" "cloudwatch_encryption" {
  name = "alias/cloudwatch-encryption-mrk"
}

data "aws_kms_alias" "event_receiver" {
  name = "alias/event-receiver-mrk"
}

data "aws_kms_alias" "dynamodb_cmk" {
  name = "alias/dynamodb-encryption-key-${local.environment.account_name}"
}

data "aws_kms_alias" "dynamodb_exports" {
  name = "alias/dynamodb-exports-${local.environment.account_name}"
}

//--------------------
// ECR Repos

data "aws_ecr_repository" "use_an_lpa_upload_statistics" {
  provider = aws.management
  name     = "use_an_lpa/stats_upload_lambda"
}

data "aws_ecr_repository" "use_an_lpa_event_receiver" {
  provider = aws.management
  name     = "use_an_lpa/event_receiver"
}

data "aws_ecr_repository" "mock_onelogin" {
  provider = aws.management
  name     = "mock-onelogin"
}

module "allow_list" {
  source = "git@github.com:ministryofjustice/opg-terraform-aws-moj-ip-allow-list.git?ref=v3.4.2"
}

data "aws_ecr_repository" "duplicate_accounts" {
  provider = aws.management
  name     = "use_an_lpa/duplicate_accounts_lambda"
}

data "aws_ecr_image" "duplicate_accounts" {
  repository_name = data.aws_ecr_repository.duplicate_accounts.name
  image_tag       = var.container_version
  provider        = aws.management
}

data "aws_iam_policy" "default_boundary" {
  count = local.environment.permissions_boundary_enabled ? 1 : 0
  name  = "opg-use-an-lpa-non-ci-boundary"
}
