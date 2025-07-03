data "aws_region" "current" {
  provider = aws.region
}

data "aws_default_tags" "current" {
  provider = aws.region
}

data "aws_caller_identity" "current" {
  provider = aws.region
}

data "aws_kms_alias" "sessions_viewer" {
  name = "alias/sessions-viewer-mrk"

  provider = aws.region
}

data "aws_kms_alias" "sessions_actor" {
  name = "alias/sessions-actor-mrk"

  provider = aws.region
}

data "aws_kms_alias" "secrets_manager" {
  name = "alias/secrets_manager_encryption-mrk"

  provider = aws.region
}

data "aws_kms_alias" "pagerduty_sns" {
  name = "alias/pagerduty-sns"

  provider = aws.region
}

data "aws_kms_alias" "cloudwatch_encryption" {
  name = "alias/cloudwatch-encryption-mrk"

  provider = aws.region
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
  name     = "pdf-service"
}

data "aws_ecr_image" "pdf_service" {
  repository_name = "pdf-service"
  image_tag       = "latest"
  provider        = aws.management
}


data "aws_ecr_repository" "use_an_lpa_admin_app" {
  provider = aws.management
  name     = "use_an_lpa/admin_app"
}

data "aws_secretsmanager_secret" "notify_api_key" {
  name = var.notify_key_secret_name

  provider = aws.region
}

data "aws_secretsmanager_secret" "gov_uk_onelogin_client_id" {
  name = var.gov_uk_onelogin_client_id_secret_name

  provider = aws.region
}

data "aws_secretsmanager_secret" "gov_uk_onelogin_identity_public_key" {
  name = var.gov_uk_onelogin_identity_public_key_secret_name

  provider = aws.region
}

data "aws_secretsmanager_secret" "gov_uk_onelogin_identity_private_key" {
  name = var.gov_uk_onelogin_identity_private_key_secret_name

  provider = aws.region
}

data "aws_secretsmanager_secret" "lpa_data_store_secret" {
  name = var.lpa_data_store_secret_name

  provider = aws.region
}

data "aws_kms_alias" "jwt_key" {
  name     = "alias/opg-data-lpa-store/${data.aws_default_tags.current.tags.account-name}/jwt-key"
  provider = aws.management
}

data "aws_secretsmanager_secret" "lpa_store_jwt_key" {
  name     = "opg-data-lpa-store/${data.aws_default_tags.current.tags.account-name}/jwt-key"
  provider = aws.management
}

data "aws_ip_ranges" "route53_healthchecks" {
  services = ["route53_healthchecks"]
  regions  = ["GLOBAL"]

  provider = aws.region
}

data "aws_security_group" "brute_force_cache_service" {
  filter {
    name   = "group-name"
    values = ["brute-force-cache-service*"]
  }

  provider = aws.region
}

data "aws_elasticache_replication_group" "brute_force_cache_replication_group" {
  replication_group_id = "brute-force-cache-replication-group"

  provider = aws.region
}

data "aws_iam_role" "ecs_autoscaling_service_role" {
  name = "AWSServiceRoleForApplicationAutoScaling_ECSService"

  provider = aws.region
}

data "aws_s3_bucket" "access_log" {
  bucket = "opg-ual-${var.account_name}-lb-access-logs-${data.aws_region.current.name}"

  provider = aws.region
}
