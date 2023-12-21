
data "aws_acm_certificate" "certificate_view" {
  domain = "${local.dev_wildcard}view.lastingpowerofattorney.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "certificate_use" {
  domain = "${local.dev_wildcard}use.lastingpowerofattorney.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "certificate_admin" {
  domain = "${local.dev_wildcard}admin.lastingpowerofattorney.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "public_facing_certificate_view" {
  domain = "${local.dev_wildcard}view-lasting-power-of-attorney.service.gov.uk"
}

data "aws_acm_certificate" "public_facing_certificate_use" {
  domain = "${local.dev_wildcard}use-lasting-power-of-attorney.service.gov.uk"
}

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

