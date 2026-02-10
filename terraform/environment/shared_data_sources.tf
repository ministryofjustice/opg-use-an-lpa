data "aws_kms_alias" "cloudwatch_encryption" {
  name = "alias/cloudwatch-encryption-mrk"
}

data "aws_kms_alias" "event_receiver" {
  name = "alias/event-receiver-mrk"
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


# data "aws_ecr_repository" "backfill" {
#  name     = "use_an_lpa/backfill_lambda"
#  provider = aws.management
#}

#data "aws_ecr_image" "backfill" {
#  repository_name = "use_an_lpa/backfill_lambda"
#  image_tag       = var.container_version
#  provider        = aws.management
#}

data "aws_ecr_repository" "mock_onelogin" {
  provider = aws.management
  name     = "mock-onelogin"
}

module "allow_list" {
  source = "git@github.com:ministryofjustice/terraform-aws-moj-ip-allow-list.git?ref=v2.3.0"
}
