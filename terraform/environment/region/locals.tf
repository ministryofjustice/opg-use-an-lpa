locals {
  policy_region_prefix = lower(replace(data.aws_region.current.name, "-", ""))

  # Replace the region in the ARN of the DynamoDB tables with the region of the current stack as the tables are created in the primary region
  # and replicated to the secondary region. This allows use to grant access to the tables in the secondary region for applications running in the secondary region.
  dynamodb_tables_arns = {
    actor_codes_table_arn     = replace(var.dynamodb_tables.actor_codes_table.arn, var.primary_region, data.aws_region.current.name)
    stats_table_arn           = replace(var.dynamodb_tables.stats_table.arn, var.primary_region, data.aws_region.current.name)
    actor_users_table_arn     = replace(var.dynamodb_tables.actor_users_table.arn, var.primary_region, data.aws_region.current.name)
    viewer_codes_table_arn    = replace(var.dynamodb_tables.viewer_codes_table.arn, var.primary_region, data.aws_region.current.name)
    viewer_activity_table_arn = replace(var.dynamodb_tables.viewer_activity_table.arn, var.primary_region, data.aws_region.current.name)
    user_lpa_actor_map_arn    = replace(var.dynamodb_tables.user_lpa_actor_map.arn, var.primary_region, data.aws_region.current.name)
  }
}
