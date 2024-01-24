data "aws_acm_certificate" "certificate_view" {
  domain = "${local.dev_wildcard}view.lastingpowerofattorney.opg.service.justice.gov.uk"

  provider = aws.region
}

data "aws_acm_certificate" "certificate_use" {
  domain = "${local.dev_wildcard}use.lastingpowerofattorney.opg.service.justice.gov.uk"

  provider = aws.region
}

data "aws_acm_certificate" "certificate_admin" {
  domain = "${local.dev_wildcard}admin.lastingpowerofattorney.opg.service.justice.gov.uk"

  provider = aws.region
}

data "aws_acm_certificate" "public_facing_certificate_view" {
  domain = "${local.dev_wildcard}view-lasting-power-of-attorney.service.gov.uk"

  provider = aws.region
}

data "aws_acm_certificate" "public_facing_certificate_use" {
  domain = "${local.dev_wildcard}use-lasting-power-of-attorney.service.gov.uk"

  provider = aws.region
}