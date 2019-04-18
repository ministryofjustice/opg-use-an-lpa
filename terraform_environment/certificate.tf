# resource "aws_route53_record" "cert_validation" {
#   provider = "aws.management"
#   name     = "${aws_acm_certificate.cert.domain_validation_options.0.resource_record_name}"
#   type     = "${aws_acm_certificate.cert.domain_validation_options.0.resource_record_type}"
#   zone_id  = "${data.aws_route53_zone.opg_service_justice_gov_uk.zone_id}"
#   records  = ["${aws_acm_certificate.cert.domain_validation_options.0.resource_record_value}"]
#   ttl      = 60
# }
# resource "aws_acm_certificate_validation" "cert" {
#   certificate_arn         = "${aws_acm_certificate.cert.arn}"
#   validation_record_fqdns = ["${aws_route53_record.cert_validation.fqdn}"]
# }
# resource "aws_acm_certificate" "cert" {
#   domain_name       = "${aws_route53_record.viewer-use-my-lpa.fqdn}"
#   validation_method = "DNS"
# }

