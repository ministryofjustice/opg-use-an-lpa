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

//------------------------
// View Certificates

resource "aws_route53_record" "certificate_validation_view" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_view.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
}

resource "aws_acm_certificate_validation" "certificate_view" {
  certificate_arn         = aws_acm_certificate.certificate_view.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_view : record.fqdn]
}

resource "aws_acm_certificate" "certificate_view" {
  domain_name       = "${local.dev_wildcard}view.lastingpowerofattorney.opg.service.justice.gov.uk"
  validation_method = "DNS"
}

resource "aws_route53_record" "certificate_validation_public_facing_view" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_public_facing_view.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.live_service_view_lasting_power_of_attorney.zone_id
}

resource "aws_acm_certificate_validation" "certificate_public_facing_view" {
  certificate_arn         = aws_acm_certificate.certificate_public_facing_view.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_public_facing_view : record.fqdn]
}

resource "aws_acm_certificate" "certificate_public_facing_view" {
  domain_name       = "${local.dev_wildcard}${data.aws_route53_zone.live_service_view_lasting_power_of_attorney.name}"
  validation_method = "DNS"
}

//------------------------
// Use Certificates

resource "aws_route53_record" "certificate_validation_use" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_use.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
}

resource "aws_acm_certificate_validation" "certificate_validation_use" {
  certificate_arn         = aws_acm_certificate.certificate_use.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_use : record.fqdn]
}

resource "aws_acm_certificate" "certificate_use" {
  domain_name       = "${local.dev_wildcard}use.lastingpowerofattorney.opg.service.justice.gov.uk"
  validation_method = "DNS"
}

resource "aws_route53_record" "certificate_validation_public_facing_use" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_public_facing_use.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.live_service_use_lasting_power_of_attorney.zone_id
}

resource "aws_acm_certificate_validation" "certificate_public_facing_use" {
  certificate_arn         = aws_acm_certificate.certificate_public_facing_use.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_public_facing_use : record.fqdn]
}

resource "aws_acm_certificate" "certificate_public_facing_use" {
  domain_name       = "${local.dev_wildcard}${data.aws_route53_zone.live_service_use_lasting_power_of_attorney.name}"
  validation_method = "DNS"
}


//------------------------
// Admin Certificates

resource "aws_route53_record" "certificate_validation_admin" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_admin.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
}

resource "aws_acm_certificate_validation" "certificate_validation_admin" {
  certificate_arn         = aws_acm_certificate.certificate_admin.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_admin : record.fqdn]
}

resource "aws_acm_certificate" "certificate_admin" {
  domain_name       = "${local.dev_wildcard}admin.lastingpowerofattorney.opg.service.justice.gov.uk"
  validation_method = "DNS"
}
