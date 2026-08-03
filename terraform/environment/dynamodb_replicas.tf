resource "aws_dynamodb_table_replica" "use_codes_table" {
  global_table_arn            = aws_dynamodb_table.use_codes_table.arn
  kms_key_arn                 = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_2.target_key_arn : null
  deletion_protection_enabled = local.environment.dynamodb_tables.replica_deletion_protection_enabled
  point_in_time_recovery      = true
  provider                    = aws.eu_west_2
}

resource "aws_dynamodb_table_replica" "stats_table" {
  global_table_arn            = aws_dynamodb_table.stats_table.arn
  kms_key_arn                 = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_2.target_key_arn : null
  deletion_protection_enabled = local.environment.dynamodb_tables.replica_deletion_protection_enabled
  point_in_time_recovery      = true
  provider                    = aws.eu_west_2
}

resource "aws_dynamodb_table_replica" "use_users_table" {
  global_table_arn            = aws_dynamodb_table.use_users_table.arn
  kms_key_arn                 = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_2.target_key_arn : null
  deletion_protection_enabled = local.environment.dynamodb_tables.replica_deletion_protection_enabled
  point_in_time_recovery      = true
  provider                    = aws.eu_west_2
}

resource "aws_dynamodb_table_replica" "viewer_codes_table" {
  global_table_arn            = aws_dynamodb_table.viewer_codes_table.arn
  kms_key_arn                 = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_2.target_key_arn : null
  deletion_protection_enabled = local.environment.dynamodb_tables.replica_deletion_protection_enabled
  point_in_time_recovery      = true
  provider                    = aws.eu_west_2
}

resource "aws_dynamodb_table_replica" "viewer_activity_table" {
  global_table_arn            = aws_dynamodb_table.viewer_activity_table.arn
  kms_key_arn                 = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_2.target_key_arn : null
  deletion_protection_enabled = local.environment.dynamodb_tables.replica_deletion_protection_enabled
  point_in_time_recovery      = true
  provider                    = aws.eu_west_2
}

resource "aws_dynamodb_table_replica" "user_lpa_actor_map" {
  global_table_arn            = aws_dynamodb_table.user_lpa_actor_map.arn
  kms_key_arn                 = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_2.target_key_arn : null
  deletion_protection_enabled = local.environment.dynamodb_tables.replica_deletion_protection_enabled
  point_in_time_recovery      = true
  provider                    = aws.eu_west_2
}
