data "aws_vpc" "default" {
  default = "true"
}

data "aws_s3_bucket" "access_log" {
  bucket = "opg-ual-${local.account_name}-lb-access-logs"
}

data "aws_subnet_ids" "private" {
  vpc_id = data.aws_vpc.default.id

  tags = {
    Name = "private"
  }
}

data "aws_subnet_ids" "public" {
  vpc_id = data.aws_vpc.default.id

  tags = {
    Name = "public"
  }
}

data "aws_cloudwatch_log_group" "use-an-lpa" {
  name = "use-an-lpa"
}

data "aws_acm_certificate" "certificate_view" {
  domain = "${local.dev_wildcard}view.lastingpowerofattorney.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "certificate_use" {
  domain = "${local.dev_wildcard}use.lastingpowerofattorney.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "public_facing_certificate_view" {
  domain = "${local.dev_wildcard}view-lasting-power-of-attorney.service.gov.uk"
}

data "aws_acm_certificate" "public_facing_certificate_use" {
  domain = "${local.dev_wildcard}use-lasting-power-of-attorney.service.gov.uk"
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
  provider = aws.management
  name     = "use_an_lpa/front_web"
}

data "aws_ecr_repository" "use_an_lpa_front_app" {
  provider = aws.management
  name     = "use_an_lpa/front_app"
}

data "aws_ecr_repository" "use_an_lpa_api_app" {
  provider = aws.management
  name     = "use_an_lpa/api_app"
}

data "aws_ecr_repository" "use_an_lpa_api_web" {
  provider = aws.management
  name     = "use_an_lpa/api_web"
}

data "aws_ecr_repository" "use_an_lpa_pdf" {
  provider = aws.management
  name     = "use_an_lpa/pdf"
}

module "whitelist" {
  source = "git@github.com:ministryofjustice/terraform-aws-moj-ip-whitelist.git"
}

data "aws_secretsmanager_secret" "notify_api_key" {
  name = "notify-api-key"
}

data "aws_ip_ranges" "route53_healthchecks" {
  services = ["route53_healthchecks"]
  regions  = ["GLOBAL"]
}

data "aws_security_group" "brute_force_cache_service" {
  name = "brute-force-cache-service"
}

data "aws_elasticache_replication_group" "brute_force_cache_replication_group" {
  replication_group_id = "brute-force-cache-replication-group"

}

data "aws_iam_role" "ecs_autoscaling_service_role" {
  name = "AWSServiceRoleForApplicationAutoScaling_ECSService"
}


# data "aws_sqs_queue" "ship_to_opg_metrics" {
#   count = local.account.ship_metrics_queue_enabled == true ? 1 : 0
#   name  = "${local.account_name}-ship-to-opg-metrics"
# }
# data "aws_lambda_function" "clsf_to_sqs" {
#   count         = local.account.ship_metrics_queue_enabled == true ? 1 : 0
#   function_name = "clsf-to-sqs"
# }
