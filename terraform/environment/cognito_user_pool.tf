
# resource "aws_cognito_user_pool" "use_a_lasting_power_of_attorney_admin" {
#   name = "use-a-lasting-power-of-attorney-admin"
#   admin_create_user_config {
#     allow_admin_create_user_only = true
#   }
#   auto_verified_attributes = ["email"]

#   password_policy {
#     minimum_length                   = 16
#     require_lowercase                = true
#     require_numbers                  = true
#     require_symbols                  = true
#     require_uppercase                = true
#     temporary_password_validity_days = 1
#   }

#   mfa_configuration = "ON"
#   device_configuration {
#     challenge_required_on_new_device      = true
#     device_only_remembered_on_user_prompt = false
#   }
#   email_configuration {
#     email_sending_account = "COGNITO_DEFAULT"
#   }
#   software_token_mfa_configuration {
#     enabled = true
#   }

#   # username_attributes =
#   username_configuration {
#     case_sensitive = false
#   }

#   account_recovery_setting {
#     recovery_mechanism {
#       name     = "verified_email"
#       priority = 1
#     }
#   }
# }

# resource "aws_cognito_user_pool_domain" "use_a_lasting_power_of_attorney_admin" {
#   domain       = "login-admin-lastingpowerofattorney"
#   user_pool_id = aws_cognito_user_pool.use_a_lasting_power_of_attorney_admin.id
# }
