locals {
  policy_region_prefix = lower(replace(data.aws_region.current.name, "-", ""))

  # The primary region is the region where the DynamoDB tables are created and replicated to the secondary region. This should not be changed once the environment is created.
  # The active region is the region where the ECS services are running. The is also the region where users will access the application.
  primary_region   = keys({ for region, region_data in var.regions : region => region_data if region_data.is_primary })[0]
  is_active_region = var.regions[data.aws_region.current.name].is_active

  # Desired count of the ECS services. Only an active region will have a desired count greater than 0.
  use_desired_count           = local.is_active_region ? var.autoscaling.use.minimum : 0
  pdf_desired_count           = local.is_active_region ? var.autoscaling.pdf.minimum : 0
  view_desired_count          = local.is_active_region ? var.autoscaling.view.minimum : 0
  api_desired_count           = local.is_active_region ? var.autoscaling.api.minimum : 0
  admin_desired_count         = local.is_active_region ? 1 : 0
  mock_onelogin_desired_count = var.environment_name != "production" && var.mock_onelogin_enabled && local.is_active_region ? 1 : 0

  # Replace the region in the ARN of the DynamoDB tables with the region of the current stack as the tables are created in the primary region
  # and replicated to the secondary region. This allows use to grant access to the tables in the secondary region for applications running in the secondary region.
  dynamodb_tables_arns = {
    use_codes_table_arn       = replace(var.dynamodb_tables.use_codes_table.arn, local.primary_region, data.aws_region.current.name)
    stats_table_arn           = replace(var.dynamodb_tables.stats_table.arn, local.primary_region, data.aws_region.current.name)
    use_users_table_arn       = replace(var.dynamodb_tables.use_users_table.arn, local.primary_region, data.aws_region.current.name)
    viewer_codes_table_arn    = replace(var.dynamodb_tables.viewer_codes_table.arn, local.primary_region, data.aws_region.current.name)
    viewer_activity_table_arn = replace(var.dynamodb_tables.viewer_activity_table.arn, local.primary_region, data.aws_region.current.name)
    user_lpa_actor_map_arn    = replace(var.dynamodb_tables.user_lpa_actor_map.arn, local.primary_region, data.aws_region.current.name)
  }

  route53_fqdns = {
    public_facing_view = local.is_active_region ? module.public_facing_view_lasting_power_of_attorney.fqdn : ""
    public_facing_use  = local.is_active_region ? module.public_facing_use_lasting_power_of_attorney.fqdn : ""
    admin              = local.is_active_region ? module.admin_use_my_lpa.fqdn : ""
    use                = local.is_active_region ? module.actor_use_my_lpa.fqdn : ""
    viewer             = local.is_active_region ? module.viewer_use_my_lpa.fqdn : ""
    mock_onelogin      = local.is_active_region ? module.mock_onelogin_use_my_lpa.fqdn : ""
  }

  onelogin_discovery_url = var.environment_name != "production" && var.mock_onelogin_enabled ?
    "http://mock-onelogin.${var.environment_name}.ual.internal.ecs:8080/.well-known/openid-configuration" :
    var.gov_uk_onelogin_discovery_url

  dev_wildcard = var.account_name == "production" ? "" : "*."
}
