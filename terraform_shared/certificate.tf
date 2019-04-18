data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = "aws.management"
  name     = "opg.service.justice.gov.uk"
}

// viewer certificates
resource "aws_route53_record" "certificate_validation_viewer" {
  provider = "aws.management"
  name     = "${aws_acm_certificate.certificate_viewer.domain_validation_options.0.resource_record_name}"
  type     = "${aws_acm_certificate.certificate_viewer.domain_validation_options.0.resource_record_type}"
  zone_id  = "${data.aws_route53_zone.opg_service_justice_gov_uk.zone_id}"
  records  = ["${aws_acm_certificate.certificate_viewer.domain_validation_options.0.resource_record_value}"]
  ttl      = 60
}

resource "aws_acm_certificate_validation" "certificate_viewer" {
  certificate_arn         = "${aws_acm_certificate.certificate_viewer.arn}"
  validation_record_fqdns = ["${aws_route53_record.certificate_validation_viewer.fqdn}"]
}

resource "aws_acm_certificate" "certificate_viewer" {
  domain_name       = "${local.dev_wildcard}viewer.${local.dns_namespace_acc}use-an-lpa.opg.service.justice.gov.uk"
  validation_method = "DNS"
}
