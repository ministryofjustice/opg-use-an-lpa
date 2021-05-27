# data "aws_cognito_user_pools" "use_a_lasting_power_of_attorney_admin" {
#   # provider = aws.identity
#   name     = "use-a-lasting-power-of-attorney-admin"
# }

# # I think I should create a client per environment in order to create the correct URLs
resource "aws_cognito_user_pool_client" "use_a_lasting_power_of_attorney_admin" {
  # provider                             = aws.identity
  name         = "${local.environment}-admin-alb-auth"
  user_pool_id = aws_cognito_user_pool.use_a_lasting_power_of_attorney_admin.id
  # user_pool_id                         = tolist(data.aws_cognito_user_pools.use_a_lasting_power_of_attorney_admin.ids)[0]
  allowed_oauth_flows                  = ["code"]
  allowed_oauth_scopes                 = ["openid"]
  supported_identity_providers         = ["COGNITO"]
  allowed_oauth_flows_user_pool_client = true
  explicit_auth_flows                  = []
  generate_secret                      = true
  access_token_validity                = 0
  id_token_validity                    = 0
  read_attributes                      = []
  write_attributes                     = []

  callback_urls = [
    "https://${aws_route53_record.admin_use_my_lpa[0].fqdn}/oauth2/idpresponse",
    "https://${aws_route53_record.admin_use_my_lpa[0].fqdn}"
  ]
  default_redirect_uri = "https://${aws_route53_record.admin_use_my_lpa[0].fqdn}/oauth2/idpresponse"
  logout_urls = [
    "https://${aws_route53_record.admin_use_my_lpa[0].fqdn}/logout",
    "https://${aws_route53_record.admin_use_my_lpa[0].fqdn}/"
  ]
}

# data "aws_ssm_parameter" "use_a_lasting_power_of_attorney_admin_domain" {
#   provider = aws.identity
#   name     = "use_a_lasting_power_of_attorney_admin_domain"
# }
