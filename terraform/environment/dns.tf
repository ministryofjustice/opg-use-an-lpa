data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

resource "aws_service_discovery_private_dns_namespace" "internal" {
  name = "${local.environment}-internal"
  vpc  = data.aws_vpc.default.id
}

//-------------------------------------------------------------
// Viewer

resource "aws_route53_record" "viewer-use-my-lpa" {
  # view.lastingpowerofattorney.opg.service.justice.gov.uk 
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${local.dns_namespace_env}view.${local.dns_namespace_acc}lastingpowerofattorney"
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

output "viewer-use-an-lpa-domain" {
  value = "https://${aws_route53_record.viewer-use-my-lpa.fqdn}"
}

//-------------------------------------------------------------
// Actor

resource "aws_route53_record" "actor-use-my-lpa" {
  # use.lastingpowerofattorney.opg.service.justice.gov.uk 
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${local.dns_namespace_env}use.${local.dns_namespace_acc}lastingpowerofattorney"
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

output "actor-use-an-lpa-domain" {
  value = "https://${aws_route53_record.actor-use-my-lpa.fqdn}"
}

