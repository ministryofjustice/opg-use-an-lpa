locals {
  policy_region_prefix = lower(replace(data.aws_region.current.name, "-", ""))

  # The primary region is the region where the DynamoDB tables are created and replicated to the secondary region.
  # The active region is the region where the ECS services are running.
  primary_region   = keys({ for region, region_data in var.regions : region => region_data if region_data.is_primary })[0]
  is_active_region = var.regions[data.aws_region.current.name].is_active

  # Desired count of the ECS services. Only an active region will have a desired count greater than 0.
  use_desired_count   = local.is_active_region ? var.autoscaling.use.minimum : 0
  pdf_desired_count   = local.is_active_region ? var.autoscaling.pdf.minimum : 0
  view_desired_count  = local.is_active_region ? var.autoscaling.view.minimum : 0
  api_desired_count   = local.is_active_region ? var.autoscaling.api.minimum : 0
  admin_desired_count = local.is_active_region ? 1 : 0

  # Replace the region in the ARN of the DynamoDB tables with the region of the current stack as the tables are created in the primary region
  # and replicated to the secondary region. This allows use to grant access to the tables in the secondary region for applications running in the secondary region.
  dynamodb_tables_arns = {
    actor_codes_table_arn     = replace(var.dynamodb_tables.actor_codes_table.arn, local.primary_region, data.aws_region.current.name)
    stats_table_arn           = replace(var.dynamodb_tables.stats_table.arn, local.primary_region, data.aws_region.current.name)
    actor_users_table_arn     = replace(var.dynamodb_tables.actor_users_table.arn, local.primary_region, data.aws_region.current.name)
    viewer_codes_table_arn    = replace(var.dynamodb_tables.viewer_codes_table.arn, local.primary_region, data.aws_region.current.name)
    viewer_activity_table_arn = replace(var.dynamodb_tables.viewer_activity_table.arn, local.primary_region, data.aws_region.current.name)
    user_lpa_actor_map_arn    = replace(var.dynamodb_tables.user_lpa_actor_map.arn, local.primary_region, data.aws_region.current.name)
  }

  route53_fqdns = {
    public_facing_view = local.is_active_region ? module.public_facing_view_lasting_power_of_attorney.fqdn : ""
    public_facing_use  = local.is_active_region ? module.public_facing_use_lasting_power_of_attorney.fqdn : ""
    admin              = local.is_active_region ? module.admin_use_my_lpa.fqdn : ""
    actor              = local.is_active_region ? module.actor_use_my_lpa.fqdn : ""
    viewer             = local.is_active_region ? module.viewer_use_my_lpa.fqdn : ""
  }

  dev_wildcard = var.account_name == "production" ? "" : "*."
}
