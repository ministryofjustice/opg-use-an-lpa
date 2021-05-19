
resource "aws_cognito_user_pool" "use_a_lasting_power_of_attorney_admin" {
  # provider = aws.identity
  name = "use-a-lasting-power-of-attorney-admin"
  admin_create_user_config {
    allow_admin_create_user_only = true
  }
  alias_attributes         = ["email"]
  auto_verified_attributes = ["email"]

  device_configuration {
    challenge_required_on_new_device = true
  }

  password_policy {
    minimum_length                   = 16
    require_lowercase                = true
    require_numbers                  = true
    require_symbols                  = true
    require_uppercase                = true
    temporary_password_validity_days = 1
  }

  mfa_configuration = "ON"

  software_token_mfa_configuration {
    enabled = true
  }

  # sms_authentication_message = "Your code is {####}"

  # sms_configuration {
  #   external_id    = "example"
  #   sns_caller_arn = aws_iam_role.example.arn
  # }

  # account_recovery_setting {
  #   recovery_mechanism {
  #     name     = "verified_email"
  #     priority = 1
  #   }
  # }
}

resource "aws_cognito_user_pool_domain" "use_a_lasting_power_of_attorney_admin" {
  domain       = "login-admin-lastingpowerofattorney"
  user_pool_id = aws_cognito_user_pool.use_a_lasting_power_of_attorney_admin.id
}

resource "aws_route53_record" "certificate_validation_login_admin" {
  provider = aws.management
  for_each = {
    for dvo in aws_acm_certificate.certificate_login_admin.domain_validation_options : dvo.domain_name => {
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
  depends_on      = [aws_acm_certificate.certificate_login_admin]
}

resource "aws_acm_certificate_validation" "certificate_validation_login_admin" {
  # provider                = aws.identity
  certificate_arn         = aws_acm_certificate.certificate_login_admin.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation_login_admin : record.fqdn]
}

resource "aws_acm_certificate" "certificate_login_admin" {
  # provider          = aws.identity
  domain_name       = "login.admin.lastingpowerofattorney.opg.service.justice.gov.uk"
  validation_method = "DNS"
}
