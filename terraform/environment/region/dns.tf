data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

data "aws_route53_zone" "live_service_use_lasting_power_of_attorney" {
  provider = aws.management
  name     = "use-lasting-power-of-attorney.service.gov.uk"
}

data "aws_route53_zone" "live_service_view_lasting_power_of_attorney" {
  provider = aws.management
  name     = "view-lasting-power-of-attorney.service.gov.uk"
}

resource "aws_service_discovery_private_dns_namespace" "internal_ecs" {
  name = "${var.environment_name}.ual.internal.ecs"
  vpc  = data.aws_vpc.default.id

  provider = aws.region
}

module "public_facing_view_lasting_power_of_attorney" {
  source = "./modules/dns"

  dns_namespace_env          = var.dns_namespace_env
  is_active_region           = local.is_active_region
  current_region             = data.aws_region.current.name
  zone_id                    = data.aws_route53_zone.live_service_view_lasting_power_of_attorney.zone_id
  loadbalancer               = aws_lb.viewer
  dns_name                   = data.aws_route53_zone.live_service_view_lasting_power_of_attorney.name
  environment_name           = var.environment_name
  create_block_email_records = true

  providers = {
    aws.us-east-1  = aws.us-east-1
    aws.management = aws.management
  }
}

module "viewer_use_my_lpa" {
  source = "./modules/dns"

  dns_namespace_env          = var.dns_namespace_env
  is_active_region           = local.is_active_region
  current_region             = data.aws_region.current.name
  zone_id                    = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  loadbalancer               = aws_lb.viewer
  dns_name                   = "view.lastingpowerofattorney"
  service_name               = "viewer"
  create_alarm               = true
  create_health_check        = true
  environment_name           = var.environment_name
  create_block_email_records = true

  providers = {
    aws.us-east-1  = aws.us-east-1
    aws.management = aws.management
  }
}

module "public_facing_use_lasting_power_of_attorney" {
  source = "./modules/dns"

  dns_namespace_env          = var.dns_namespace_env
  is_active_region           = local.is_active_region
  current_region             = data.aws_region.current.name
  zone_id                    = data.aws_route53_zone.live_service_use_lasting_power_of_attorney.zone_id
  dns_name                   = data.aws_route53_zone.live_service_use_lasting_power_of_attorney.name
  loadbalancer               = aws_lb.actor
  environment_name           = var.environment_name
  create_block_email_records = true

  providers = {
    aws.us-east-1  = aws.us-east-1
    aws.management = aws.management
  }
}

module "actor_use_my_lpa" {
  source = "./modules/dns"

  dns_namespace_env          = var.dns_namespace_env
  is_active_region           = local.is_active_region
  current_region             = data.aws_region.current.name
  zone_id                    = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  loadbalancer               = aws_lb.actor
  dns_name                   = "use.lastingpowerofattorney"
  service_name               = "actor"
  create_alarm               = true
  create_health_check        = true
  environment_name           = var.environment_name
  create_block_email_records = true

  providers = {
    aws.us-east-1  = aws.us-east-1
    aws.management = aws.management
  }
}

module "admin_use_my_lpa" {
  source = "./modules/dns"

  dns_namespace_env          = var.dns_namespace_env
  is_active_region           = local.is_active_region
  current_region             = data.aws_region.current.name
  zone_id                    = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  loadbalancer               = aws_lb.admin
  service_name               = "admin"
  dns_name                   = "admin.lastingpowerofattorney"
  environment_name           = var.environment_name
  create_block_email_records = true

  providers = {
    aws.us-east-1  = aws.us-east-1
    aws.management = aws.management
  }
}
