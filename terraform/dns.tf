data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = "aws.management"
  name     = "opg.service.justice.gov.uk"
}

resource "aws_route53_record" "view-use-my-lpa" {
  provider = "aws.management"
  zone_id  = "${data.aws_route53_zone.opg_service_justice_gov_uk.zone_id}"
  name     = "view.${local.dns_prefix}"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = "${aws_lb.view.dns_name}"
    zone_id                = "${aws_lb.view.zone_id}"
  }

  lifecycle {
    create_before_destroy = true
  }
}

output "view-use-an-lpa-domain" {
  value = "${aws_route53_record.view-use-my-lpa.fqdn}"
}
