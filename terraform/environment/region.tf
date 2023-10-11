module "eu_west_1" {
  source = "./region"

  alb_tg_arns = {
    "actor"  = aws_lb_target_group.actor
    "viewer" = aws_lb_target_group.viewer
    "admin"  = aws_lb_target_group.admin
  }

  autoscaling = local.environment.autoscaling

  application_logs_name = aws_cloudwatch_log_group.application_logs.name

  dynamodb_tables = {
    "actor_codes_table"     = aws_dynamodb_table.actor_codes_table
    "stats_table"           = aws_dynamodb_table.stats_table
    "actor_users_table"     = aws_dynamodb_table.actor_users_table
    "viewer_codes_table"    = aws_dynamodb_table.viewer_codes_table
    "viewer_activity_table" = aws_dynamodb_table.viewer_activity_table
    "user_lpa_actor_map"    = aws_dynamodb_table.user_lpa_actor_map
  }

  cognito_user_pool_id = aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin.id

  environment_name = local.environment_name

  actor_loadbalancer_security_group_id  = aws_security_group.actor_loadbalancer.id
  viewer_loadbalancer_security_group_id = aws_security_group.viewer_loadbalancer.id
  admin_loadbalancer_security_group_id  = aws_security_group.admin_loadbalancer.id

  notify_key_secret_name = local.environment.notify_key_secret_name

  lpa_codes_endpoint       = local.environment.lpa_codes_endpoint
  iap_images_endpoint      = local.environment.iap_images_endpoint
  lpas_collection_endpoint = local.environment.lpas_collection_endpoint

  logging_level = local.environment.logging_level

  parameter_store_arns = [aws_ssm_parameter.system_message_view_en.arn, aws_ssm_parameter.system_message_view_cy.arn, aws_ssm_parameter.system_message_use_en.arn, aws_ssm_parameter.system_message_use_cy.arn, ]
  route_53_fqdns = {
    "public_view" = aws_route53_record.public_facing_view_lasting_power_of_attorney.fqdn
    "public_use"  = aws_route53_record.public_facing_use_lasting_power_of_attorney.fqdn
    "admin"       = aws_route53_record.admin_use_my_lpa.fqdn
  }

  feature_flags = {
    "allow_gov_one_login"                                        = local.environment.application_flags.allow_gov_one_login
    "instructions_and_preferences"                               = local.environment.application_flags.instructions_and_preferences
    "dont_send_lpas_registered_after_sep_2019_to_cleansing_team" = local.environment.application_flags.dont_send_lpas_registered_after_sep_2019_to_cleansing_team
    "allow_meris_lpas"                                           = local.environment.application_flags.allow_meris_lpas
    "deploy_opentelemetry_sidecar"                               = local.environment.deploy_opentelemetry_sidecar
    "delete_lpa_feature"                                         = local.environment.application_flags.delete_lpa_feature
  }

  container_version       = var.container_version
  admin_container_version = var.admin_container_version

  sirius_account_id = local.environment.sirius_account_id

  ecs_task_roles     = module.iam.ecs_task_roles
  ecs_execution_role = module.iam.ecs_execution_role

  admin_cognito_user_pool_domain_name = local.admin_cognito_user_pool_domain_name

  capacity_provider = local.capacity_provider

  aws_service_discovery_service = aws_service_discovery_private_dns_namespace.internal_ecs


  session_expires_use      = local.environment.session_expires_use
  session_expiry_warning   = local.environment.session_expiry_warning
  cookie_expires_use       = local.environment.cookie_expires_use
  google_analytics_id_use  = local.environment.google_analytics_id_use
  google_analytics_id_view = local.environment.google_analytics_id_view
  cookie_expires_view      = local.environment.cookie_expires_view
  session_expires_view     = local.environment.session_expires_view

  providers = {
    aws.region     = aws.eu_west_1
    aws.management = aws.management
  }
}