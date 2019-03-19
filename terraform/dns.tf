data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = "aws.management"
  name     = "opg.service.justice.gov.uk"
}

resource "aws_route53_record" "use-my-lpa" {
  provider = "aws.management"
  zone_id  = "${data.aws_route53_zone.opg_service_justice_gov_uk.zone_id}"
  name     = "${local.dns_prefix}"
  type     = "A"

  #   alias {
  #     evaluate_target_health = false
  #     name                   = "${aws_lb.load_balancer.dns_name}"
  #     zone_id                = "${aws_lb.load_balancer.zone_id}"
  #   }
}
