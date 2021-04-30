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

resource "aws_service_discovery_private_dns_namespace" "internal" {
  name = "${local.environment}-internal"
  vpc  = data.aws_vpc.default.id
}

//-------------------------------------------------------------
// View

resource "aws_route53_record" "public_facing_view_lasting_power_of_attorney" {
  # view-lasting-power-of-attorney.service.gov.uk
  provider = aws.management
  zone_id  = data.aws_route53_zone.live_service_view_lasting_power_of_attorney.zone_id
  name     = "${local.dns_namespace_env}${data.aws_route53_zone.live_service_view_lasting_power_of_attorney.name}"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = aws_lb.viewer.dns_name
    zone_id                = aws_lb.viewer.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

output "public_facing_view_domain" {
  value = "https://${aws_route53_record.public_facing_view_lasting_power_of_attorney.fqdn}"
}

resource "aws_route53_record" "viewer-use-my-lpa" {
  # view.lastingpowerofattorney.opg.service.justice.gov.uk
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${local.dns_namespace_env}view.lastingpowerofattorney"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = aws_lb.viewer.dns_name
    zone_id                = aws_lb.viewer.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}


//-------------------------------------------------------------
// Use

resource "aws_route53_record" "public_facing_use_lasting_power_of_attorney" {
  # use-lasting-power-of-attorney.service.gov.uk
  provider = aws.management
  zone_id  = data.aws_route53_zone.live_service_use_lasting_power_of_attorney.zone_id
  name     = "${local.dns_namespace_env}${data.aws_route53_zone.live_service_use_lasting_power_of_attorney.name}"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = aws_lb.actor.dns_name
    zone_id                = aws_lb.actor.zone_id
  }
  lifecycle {
    create_before_destroy = true
  }
}

output "public_facing_use_domain" {
  value = "https://${aws_route53_record.public_facing_use_lasting_power_of_attorney.fqdn}"
}

resource "aws_route53_record" "actor-use-my-lpa" {
  # use.lastingpowerofattorney.opg.service.justice.gov.uk
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${local.dns_namespace_env}use.lastingpowerofattorney"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = aws_lb.actor.dns_name
    zone_id                = aws_lb.actor.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}


resource "aws_route53_record" "admin-use-my-lpa" {
  # admin.lastingpowerofattorney.opg.service.justice.gov.uk
  count    = local.account.build_admin == true ? 1 : 0
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${local.dns_namespace_env}admin.lastingpowerofattorney"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = aws_lb.admin[0].dns_name
    zone_id                = aws_lb.admin[0].zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}
