# data "aws_cognito_user_pools" "use_a_lasting_power_of_attorney_admin" {
#   name = "use-a-lasting-power-of-attorney-admin"
# }

# # I think I should create a client per environment in order to create the correct URLs
# resource "aws_cognito_user_pool_client" "use_a_lasting_power_of_attorney_admin" {
#   name                                 = "${local.environment}-admin-alb-auth"
#   user_pool_id                         = data.aws_cognito_user_pool.use_a_lasting_power_of_attorney_admin.id
#   allowed_oauth_flows                  = ["code"]
#   allowed_oauth_scopes                 = ["openid"]
#   supported_identity_providers         = ["COGNITO"]
#   allowed_oauth_flows_user_pool_client = true

#   callback_urls = [
#     "https://${aws_route53_record.admin-use-my-lpa.fqdn}/oauth2/idpresponse",
#     "https://${aws_route53_record.admin-use-my-lpa.fqdn}"
#   ]
#   default_redirect_uri = "https://${aws_route53_record.admin-use-my-lpa.fqdn}/oauth2/idpresponse"
#   logout_urls = [
#     "https://${aws_route53_record.admin-use-my-lpa.fqdn}/logout",
#     "https://${aws_route53_record.admin-use-my-lpa.fqdn}/"
#   ]
# }
