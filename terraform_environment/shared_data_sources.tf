data "aws_vpc" "default" {
  default = "true"
}

data "aws_s3_bucket" "access_log" {
  bucket = "opg-ual-${local.account_name}-lb-access-logs"
}

data "aws_subnet_ids" "private" {
  vpc_id = "${data.aws_vpc.default.id}"

  tags = {
    Name = "*private*"
  }
}

data "aws_subnet" "private" {
  count = "${length(data.aws_subnet_ids.private.ids)}"
  id    = "${data.aws_subnet_ids.private.ids[count.index]}"
}

data "aws_subnet_ids" "public" {
  vpc_id = "${data.aws_vpc.default.id}"

  tags = {
    Name = "public"
  }
}

data "aws_subnet" "public" {
  count = "${length(data.aws_subnet_ids.public.ids)}"
  id    = "${data.aws_subnet_ids.public.ids[count.index]}"
}

data "aws_cloudwatch_log_group" "use-an-lpa" {
  name = "use-an-lpa"
}

data "aws_acm_certificate" "certificate_viewer" {
  domain = "${local.dev_wildcard}viewer.${local.dns_namespace_acc}use-an-lpa.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "certificate_actor" {
  domain = "${local.dev_wildcard}actor.${local.dns_namespace_acc}use-an-lpa.opg.service.justice.gov.uk"
}

data "aws_kms_alias" "sessions_viewer" {
  name = "alias/sessions-viewer"
}

data "aws_kms_alias" "sessions_actor" {
  name = "alias/sessions-actor"
}

//--------------------
// ECR Repos

data "aws_ecr_repository" "use_an_lpa_front_web" {
  provider = "aws.management"
  name     = "use_an_lpa/front_web"
}

data "aws_ecr_repository" "use_an_lpa_front_app" {
  provider = "aws.management"
  name     = "use_an_lpa/front_app"
}

data "aws_ecr_repository" "use_an_lpa_api_app" {
  provider = "aws.management"
  name     = "use_an_lpa/api_app"
}

data "aws_ecr_repository" "use_an_lpa_api_web" {
  provider = "aws.management"
  name     = "use_an_lpa/api_web"
}

module "whitelist" {
  source = "git@github.com:ministryofjustice/terraform-aws-moj-ip-whitelist.git"
}

data "aws_secretsmanager_secret" "notify_api_key" {
  name = "notify-api-key"
}

data "aws_secretsmanager_secret_version" "notify_api_key" {
  secret_id = "${data.aws_secretsmanager_secret.notify_api_key.id}"
}
