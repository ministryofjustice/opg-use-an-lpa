data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

//------------------------
// View Certificates

resource "aws_route53_record" "certificate_validation_view" {
  provider = aws.management
  name     = aws_acm_certificate.certificate_view.domain_validation_options[0].resource_record_name
  type     = aws_acm_certificate.certificate_view.domain_validation_options[0].resource_record_type
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  records  = [aws_acm_certificate.certificate_view.domain_validation_options[0].resource_record_value]
  ttl      = 60
}

resource "aws_acm_certificate_validation" "certificate_view" {
  certificate_arn         = aws_acm_certificate.certificate_view.arn
  validation_record_fqdns = [aws_route53_record.certificate_validation_view.fqdn]
}

resource "aws_acm_certificate" "certificate_view" {
  domain_name       = "${local.dev_wildcard}view.lastingpowerofattorney.opg.service.justice.gov.uk"
  validation_method = "DNS"
}

//------------------------
// Use Certificates

resource "aws_route53_record" "certificate_validation_use" {
  provider = aws.management
  name     = aws_acm_certificate.certificate_use.domain_validation_options[0].resource_record_name
  type     = aws_acm_certificate.certificate_use.domain_validation_options[0].resource_record_type
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  records  = [aws_acm_certificate.certificate_use.domain_validation_options[0].resource_record_value]
  ttl      = 60
}

resource "aws_acm_certificate_validation" "certificate_use" {
  certificate_arn         = aws_acm_certificate.certificate_use.arn
  validation_record_fqdns = [aws_route53_record.certificate_validation_use.fqdn]
}

resource "aws_acm_certificate" "certificate_use" {
  domain_name       = "${local.dev_wildcard}use.lastingpowerofattorney.opg.service.justice.gov.uk"
  validation_method = "DNS"
}
