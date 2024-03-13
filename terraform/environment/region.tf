module "eu_west_1" {

  count = local.environment.regions["eu-west-1"].enabled ? 1 : 0

  source = "./region"

  account_name                              = local.environment.account_name
  admin_container_version                   = var.admin_container_version
  autoscaling                               = local.environment.autoscaling
  associate_alb_with_waf_web_acl_enabled    = local.environment.associate_alb_with_waf_web_acl_enabled
  capacity_provider                         = local.capacity_provider
  container_version                         = var.container_version
  cookie_expires_use                        = local.environment.cookie_expires_use
  cookie_expires_view                       = local.environment.cookie_expires_view
  create_dashboard                          = local.environment.create_dashboard
  dns_namespace_env                         = local.dns_namespace_env
  ecs_execution_role                        = module.iam.ecs_execution_role
  ecs_task_roles                            = module.iam.ecs_task_roles
  environment_name                          = local.environment_name
  google_analytics_id_use                   = local.environment.google_analytics_id_use
  google_analytics_id_view                  = local.environment.google_analytics_id_view
  gov_uk_onelogin_client_id_secret_name     = local.environment.gov_uk_onelogin_client_id_secret_name
  iap_images_endpoint                       = local.environment.iap_images_endpoint
  load_balancer_deletion_protection_enabled = local.environment.load_balancer_deletion_protection_enabled
  logging_level                             = local.environment.logging_level
  log_retention_days                        = local.environment.log_retention_in_days
  lpa_codes_endpoint                        = local.environment.lpa_codes_endpoint
  lpas_collection_endpoint                  = local.environment.lpas_collection_endpoint
  moj_sites                                 = module.allow_list.moj_sites
  notify_key_secret_name                    = local.environment.notify_key_secret_name
  parameter_store_arns                      = [aws_ssm_parameter.system_message_view_en.arn, aws_ssm_parameter.system_message_view_cy.arn, aws_ssm_parameter.system_message_use_en.arn, aws_ssm_parameter.system_message_use_cy.arn]
  pagerduty_service_id                      = local.environment.pagerduty_service_id
  pdf_container_version                     = local.environment.pdf_container_version
  public_access_enabled                     = var.public_access_enabled
  regions                                   = local.environment.regions
  session_expires_use                       = local.environment.session_expires_use
  session_expires_view                      = local.environment.session_expires_view
  session_expiry_warning                    = local.environment.session_expiry_warning
  ship_metrics_queue_enabled                = local.environment.ship_metrics_queue_enabled
  sirius_account_id                         = local.environment.sirius_account_id

  admin_cognito = {
    id                          = aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin.id
    user_pool_id                = local.admin_cognito_user_pool_id
    user_pool_domain_name       = local.admin_cognito_user_pool_domain_name
    user_pool_client_secret     = aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin.client_secret
    user_pool_id_token_validity = aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin.id_token_validity
  }

  dynamodb_tables = {
    "use_codes_table"       = aws_dynamodb_table.use_codes_table
    "stats_table"           = aws_dynamodb_table.stats_table
    "use_users_table"       = aws_dynamodb_table.use_users_table
    "viewer_codes_table"    = aws_dynamodb_table.viewer_codes_table
    "viewer_activity_table" = aws_dynamodb_table.viewer_activity_table
    "user_lpa_actor_map"    = aws_dynamodb_table.user_lpa_actor_map
  }

  feature_flags = {
    "allow_gov_one_login"                                        = local.environment.application_flags.allow_gov_one_login
    "instructions_and_preferences"                               = local.environment.application_flags.instructions_and_preferences
    "dont_send_lpas_registered_after_sep_2019_to_cleansing_team" = local.environment.application_flags.dont_send_lpas_registered_after_sep_2019_to_cleansing_team
    "allow_meris_lpas"                                           = local.environment.application_flags.allow_meris_lpas
    "deploy_opentelemetry_sidecar"                               = local.environment.deploy_opentelemetry_sidecar
    "delete_lpa_feature"                                         = local.environment.application_flags.delete_lpa_feature
  }

  providers = {
    aws.region     = aws.eu_west_1
    aws.management = aws.management
    aws.us-east-1  = aws.us-east-1
  }
}

moved {
  from = module.eu_west_1
  to   = module.eu_west_1[0]
}

module "eu_west_2" {

  count = local.environment.regions["eu-west-2"].enabled ? 1 : 0

  source = "./region"

  account_name                              = local.environment.account_name
  admin_container_version                   = var.admin_container_version
  autoscaling                               = local.environment.autoscaling
  associate_alb_with_waf_web_acl_enabled    = local.environment.associate_alb_with_waf_web_acl_enabled
  capacity_provider                         = local.capacity_provider
  container_version                         = var.container_version
  cookie_expires_use                        = local.environment.cookie_expires_use
  cookie_expires_view                       = local.environment.cookie_expires_view
  create_dashboard                          = local.environment.create_dashboard
  dns_namespace_env                         = local.dns_namespace_env
  ecs_execution_role                        = module.iam.ecs_execution_role
  ecs_task_roles                            = module.iam.ecs_task_roles
  environment_name                          = local.environment_name
  google_analytics_id_use                   = local.environment.google_analytics_id_use
  google_analytics_id_view                  = local.environment.google_analytics_id_view
  gov_uk_onelogin_client_id_secret_name     = local.environment.gov_uk_onelogin_client_id_secret_name
  iap_images_endpoint                       = local.environment.iap_images_endpoint
  load_balancer_deletion_protection_enabled = local.environment.load_balancer_deletion_protection_enabled
  logging_level                             = local.environment.logging_level
  log_retention_days                        = local.environment.log_retention_in_days
  lpa_codes_endpoint                        = local.environment.lpa_codes_endpoint
  lpas_collection_endpoint                  = local.environment.lpas_collection_endpoint
  moj_sites                                 = module.allow_list.moj_sites
  notify_key_secret_name                    = local.environment.notify_key_secret_name
  parameter_store_arns                      = [aws_ssm_parameter.system_message_view_en.arn, aws_ssm_parameter.system_message_view_cy.arn, aws_ssm_parameter.system_message_use_en.arn, aws_ssm_parameter.system_message_use_cy.arn]
  pagerduty_service_id                      = local.environment.pagerduty_service_id
  pdf_container_version                     = local.environment.pdf_container_version
  public_access_enabled                     = var.public_access_enabled
  regions                                   = local.environment.regions
  session_expires_use                       = local.environment.session_expires_use
  session_expires_view                      = local.environment.session_expires_view
  session_expiry_warning                    = local.environment.session_expiry_warning
  ship_metrics_queue_enabled                = local.environment.ship_metrics_queue_enabled
  sirius_account_id                         = local.environment.sirius_account_id

  admin_cognito = {
    id                          = aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin.id
    user_pool_id                = local.admin_cognito_user_pool_id
    user_pool_domain_name       = local.admin_cognito_user_pool_domain_name
    user_pool_client_secret     = aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin.client_secret
    user_pool_id_token_validity = aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin.id_token_validity
  }

  dynamodb_tables = {
    "use_codes_table"       = aws_dynamodb_table.use_codes_table
    "stats_table"           = aws_dynamodb_table.stats_table
    "use_users_table"       = aws_dynamodb_table.use_users_table
    "viewer_codes_table"    = aws_dynamodb_table.viewer_codes_table
    "viewer_activity_table" = aws_dynamodb_table.viewer_activity_table
    "user_lpa_actor_map"    = aws_dynamodb_table.user_lpa_actor_map
  }

  feature_flags = {
    "allow_gov_one_login"                                        = local.environment.application_flags.allow_gov_one_login
    "instructions_and_preferences"                               = local.environment.application_flags.instructions_and_preferences
    "dont_send_lpas_registered_after_sep_2019_to_cleansing_team" = local.environment.application_flags.dont_send_lpas_registered_after_sep_2019_to_cleansing_team
    "allow_meris_lpas"                                           = local.environment.application_flags.allow_meris_lpas
    "deploy_opentelemetry_sidecar"                               = local.environment.deploy_opentelemetry_sidecar
    "delete_lpa_feature"                                         = local.environment.application_flags.delete_lpa_feature
  }

  providers = {
    aws.region     = aws.eu_west_2
    aws.management = aws.management
    aws.us-east-1  = aws.us-east-1
  }
}

moved {
  from = module.eu_west_2
  to   = module.eu_west_2[0]
}
