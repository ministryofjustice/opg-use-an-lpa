data "aws_cognito_user_pools" "use_a_lasting_power_of_attorney_admin" {
  provider = aws.identity
  name     = "use-a-lasting-power-of-attorney-admin"
}

data "aws_ssm_parameter" "use_a_lasting_power_of_attorney_admin_domain" {
  provider = aws.identity
  name     = "use_a_lasting_power_of_attorney_admin_domain"
}

locals {
  admin_cognito_user_pool_id          = tolist(data.aws_cognito_user_pools.use_a_lasting_power_of_attorney_admin.ids)[0]
  admin_cognito_user_pool_domain_name = "https://${data.aws_ssm_parameter.use_a_lasting_power_of_attorney_admin_domain.value}.auth.eu-west-1.amazoncognito.com"
}

resource "aws_cognito_user_pool_client" "use_a_lasting_power_of_attorney_admin" {
  count                                = local.environment.build_admin == true ? 1 : 0
  provider                             = aws.identity
  name                                 = "${local.environment_name}-admin-auth"
  user_pool_id                         = local.admin_cognito_user_pool_id
  allowed_oauth_flows                  = ["code"]
  allowed_oauth_scopes                 = ["openid"]
  supported_identity_providers         = ["COGNITO"]
  allowed_oauth_flows_user_pool_client = true
  explicit_auth_flows = [
    "ALLOW_CUSTOM_AUTH",
    "ALLOW_REFRESH_TOKEN_AUTH",
    "ALLOW_USER_SRP_AUTH",
  ]

  generate_secret = true

  token_validity_units {
    access_token  = "minutes"
    id_token      = "seconds"
    refresh_token = "days"
  }

  access_token_validity  = local.environment.session_expires_admin
  id_token_validity      = 3600
  refresh_token_validity = 1
  read_attributes        = []
  write_attributes       = []

  callback_urls = ["https://${aws_route53_record.admin_use_my_lpa[0].fqdn}/oauth2/idpresponse"]
  logout_urls   = ["https://${aws_route53_record.admin_use_my_lpa[0].fqdn}/logout"]
}
