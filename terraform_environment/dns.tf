data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = "aws.management"
  name     = "opg.service.justice.gov.uk"
}

resource "aws_route53_record" "viewer-use-my-lpa" {
  provider = "aws.management"
  zone_id  = "${data.aws_route53_zone.opg_service_justice_gov_uk.zone_id}"
  name     = "viewer.${local.dns_prefix}"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = "${aws_lb.viewer.dns_name}"
    zone_id                = "${aws_lb.viewer.zone_id}"
  }

  lifecycle {
    create_before_destroy = true
  }
}

output "viewer-use-an-lpa-domain" {
  value = "${aws_route53_record.viewer-use-my-lpa.fqdn}"
}
