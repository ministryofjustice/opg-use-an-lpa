resource "aws_dynamodb_table" "use_codes_table" {
  name                        = "${local.environment_name}-${local.environment.dynamodb_tables.actor_codes.name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "ActorCode"
  stream_enabled              = true
  stream_view_type            = "NEW_AND_OLD_IMAGES"
  deletion_protection_enabled = local.environment.dynamodb_tables.deletion_protection_enabled
  server_side_encryption {
    enabled     = true
    kms_key_arn = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_1.target_key_arn : null
  }

  attribute {
    name = "ActorCode"
    type = "S"
  }

  point_in_time_recovery {
    enabled = true
  }

  # Replicas are managed as standalone aws_dynamodb_table_replica resources in dynamodb_replicas.tf.
  lifecycle {
    ignore_changes = [replica]
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "stats_table" {
  name                        = "${local.environment_name}-${local.environment.dynamodb_tables.stats.name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "TimePeriod"
  stream_enabled              = true
  stream_view_type            = "NEW_AND_OLD_IMAGES"
  deletion_protection_enabled = local.environment.dynamodb_tables.deletion_protection_enabled

  server_side_encryption {
    enabled     = true
    kms_key_arn = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_1.target_key_arn : null
  }

  attribute {
    name = "TimePeriod"
    type = "S"
  }


  point_in_time_recovery {
    enabled = true
  }

  # Replicas are managed as standalone aws_dynamodb_table_replica resources in dynamodb_replicas.tf.
  lifecycle {
    ignore_changes = [replica]
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "use_users_table" {
  name                        = "${local.environment_name}-${local.environment.dynamodb_tables.actor_users.name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "Id"
  stream_enabled              = true
  stream_view_type            = "NEW_AND_OLD_IMAGES"
  deletion_protection_enabled = local.environment.dynamodb_tables.deletion_protection_enabled

  server_side_encryption {
    enabled     = true
    kms_key_arn = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_1.target_key_arn : null
  }

  attribute {
    name = "Id"
    type = "S"
  }
  attribute {
    name = "Identity"
    type = "S"
  }
  attribute {
    name = "Email"
    type = "S"
  }

  global_secondary_index {
    name            = "IdentityIndex"
    hash_key        = "Identity"
    projection_type = "ALL"
  }
  global_secondary_index {
    name            = "EmailIndex"
    hash_key        = "Email"
    projection_type = "ALL"
  }

  ttl {
    attribute_name = "ExpiresTTL"
    enabled        = true
  }

  point_in_time_recovery {
    enabled = true
  }

  # Replicas are managed as standalone aws_dynamodb_table_replica resources in dynamodb_replicas.tf.
  lifecycle {
    ignore_changes = [replica]
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "viewer_codes_table" {
  name                        = "${local.environment_name}-${local.environment.dynamodb_tables.viewer_codes.name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "ViewerCode"
  stream_enabled              = true
  stream_view_type            = "NEW_AND_OLD_IMAGES"
  deletion_protection_enabled = local.environment.dynamodb_tables.deletion_protection_enabled
  server_side_encryption {
    enabled     = true
    kms_key_arn = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_1.target_key_arn : null
  }

  attribute {
    name = "ViewerCode"
    type = "S"
  }

  attribute {
    name = "SiriusUid"
    type = "S"
  }

  attribute {
    name = "Expires"
    type = "S"
  }

  global_secondary_index {
    name            = "SiriusUidIndex"
    hash_key        = "SiriusUid"
    range_key       = "Expires"
    projection_type = "ALL"
  }

  point_in_time_recovery {
    enabled = true
  }

  # Replicas are managed as standalone aws_dynamodb_table_replica resources in dynamodb_replicas.tf.
  lifecycle {
    ignore_changes = [replica]
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "viewer_activity_table" {
  name                        = "${local.environment_name}-${local.environment.dynamodb_tables.viewer_activity.name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "ViewerCode"
  range_key                   = "Viewed"
  stream_enabled              = true
  stream_view_type            = "NEW_AND_OLD_IMAGES"
  deletion_protection_enabled = local.environment.dynamodb_tables.deletion_protection_enabled

  server_side_encryption {
    enabled     = true
    kms_key_arn = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_1.target_key_arn : null
  }

  attribute {
    name = "ViewerCode"
    type = "S"
  }
  attribute {
    name = "Viewed"
    type = "S"
  }


  point_in_time_recovery {
    enabled = true
  }

  # Replicas are managed as standalone aws_dynamodb_table_replica resources in dynamodb_replicas.tf.
  lifecycle {
    ignore_changes = [replica]
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "user_lpa_actor_map" {
  name                        = "${local.environment_name}-${local.environment.dynamodb_tables.user_lpa_actor_map.name}"
  billing_mode                = "PAY_PER_REQUEST"
  hash_key                    = "Id"
  stream_enabled              = true
  stream_view_type            = "NEW_AND_OLD_IMAGES"
  deletion_protection_enabled = local.environment.dynamodb_tables.deletion_protection_enabled

  server_side_encryption {
    enabled     = true
    kms_key_arn = local.environment.dynamodb_tables.cmk_encryption_enabled ? data.aws_kms_alias.dynamodb_cmk_eu_west_1.target_key_arn : null
  }

  attribute {
    name = "Id"
    type = "S"
  }

  attribute {
    name = "UserId"
    type = "S"
  }

  attribute {
    name = "ActivationCode"
    type = "S"
  }

  attribute {
    name = "SiriusUid"
    type = "S"
  }

  global_secondary_index {
    name            = "ActivationCodeIndex"
    hash_key        = "ActivationCode"
    projection_type = "ALL"
  }

  global_secondary_index {
    name            = "UserIndex"
    hash_key        = "UserId"
    projection_type = "ALL"
  }

  global_secondary_index {
    name            = "SiriusUidIndex"
    hash_key        = "SiriusUid"
    projection_type = "ALL"
  }

  ttl {
    attribute_name = "ActivateBy"
    enabled        = true
  }

  point_in_time_recovery {
    enabled = true
  }

  # Replicas are managed as standalone aws_dynamodb_table_replica resources in dynamodb_replicas.tf.
  lifecycle {
    ignore_changes = [replica]
  }

  provider = aws.eu_west_1
}

data "aws_kms_alias" "dynamodb_cmk_eu_west_1" {
  provider = aws.eu_west_1
  name     = "alias/dynamodb-encryption-key-${local.environment.account_name}"
}

data "aws_kms_alias" "dynamodb_cmk_eu_west_2" {
  provider = aws.eu_west_2
  name     = "alias/dynamodb-encryption-key-${local.environment.account_name}"
}
