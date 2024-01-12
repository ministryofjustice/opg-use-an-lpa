data "aws_kms_alias" "cloudwatch_encryption" {
  name = "alias/cloudwatch-encryption-mrk"
}

//--------------------
// ECR Repos

data "aws_ecr_repository" "use_an_lpa_upload_statistics" {
  provider = aws.management
  name     = "use_an_lpa/stats_upload_lambda"
}

module "allow_list" {
  source = "git@github.com:ministryofjustice/terraform-aws-moj-ip-allow-list.git?ref=v2.3.0"
}

