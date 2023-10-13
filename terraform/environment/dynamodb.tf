resource "aws_dynamodb_table" "actor_codes_table" {
  name             = "${local.environment_name}-${local.environment.dynamodb_tables.actor_codes.name}"
  billing_mode     = "PAY_PER_REQUEST"
  hash_key         = "ActorCode"
  stream_enabled   = true
  stream_view_type = "NEW_IMAGE"
  server_side_encryption {
    enabled = true
  }

  attribute {
    name = "ActorCode"
    type = "S"
  }

  point_in_time_recovery {
    enabled = true
  }

  # For each region in the environment that is not the primary_region, create a DynamoDB replica.

  dynamic "replica" {
    for_each = [
      for region in local.environment.regions : region
      if region.is_primary != true
    ]

    content {
      region_name    = replica.value.name
      propagate_tags = true
    }
  }

  lifecycle {
    prevent_destroy = false
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "stats_table" {
  name             = "${local.environment_name}-${local.environment.dynamodb_tables.stats.name}"
  billing_mode     = "PAY_PER_REQUEST"
  hash_key         = "TimePeriod"
  stream_enabled   = true
  stream_view_type = "NEW_IMAGE"
  #tfsec:ignore:aws-dynamodb-table-customer-key - same as the other tables. Will update in one go as separate ticket
  server_side_encryption {
    enabled = true
  }

  attribute {
    name = "TimePeriod"
    type = "S"
  }

  point_in_time_recovery {
    enabled = true
  }

  dynamic "replica" {
    for_each = [
      for region in local.environment.regions : region
      if region.is_primary != true
    ]

    content {
      region_name    = replica.value.name
      propagate_tags = true
    }
  }

  lifecycle {
    prevent_destroy = false
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "actor_users_table" {
  name             = "${local.environment_name}-${local.environment.dynamodb_tables.actor_users.name}"
  billing_mode     = "PAY_PER_REQUEST"
  hash_key         = "Id"
  stream_enabled   = true
  stream_view_type = "NEW_IMAGE"
  server_side_encryption {
    enabled = true
  }

  attribute {
    name = "Id"
    type = "S"
  }
  attribute {
    name = "Email"
    type = "S"
  }
  attribute {
    name = "NewEmail"
    type = "S"
  }
  attribute {
    name = "ActivationToken"
    type = "S"
  }
  attribute {
    name = "PasswordResetToken"
    type = "S"
  }
  attribute {
    name = "EmailResetToken"
    type = "S"
  }

  global_secondary_index {
    name            = "EmailIndex"
    hash_key        = "Email"
    projection_type = "ALL"
  }
  global_secondary_index {
    name            = "NewEmailIndex"
    hash_key        = "NewEmail"
    projection_type = "ALL"
  }
  global_secondary_index {
    name            = "ActivationTokenIndex"
    hash_key        = "ActivationToken"
    projection_type = "KEYS_ONLY"
  }
  global_secondary_index {
    name            = "PasswordResetTokenIndex"
    hash_key        = "PasswordResetToken"
    projection_type = "KEYS_ONLY"
  }
  global_secondary_index {
    name            = "EmailResetTokenIndex"
    hash_key        = "EmailResetToken"
    projection_type = "KEYS_ONLY"
  }

  ttl {
    attribute_name = "ExpiresTTL"
    enabled        = true
  }

  point_in_time_recovery {
    enabled = true
  }

  dynamic "replica" {
    for_each = [
      for region in local.environment.regions : region
      if region.is_primary != true
    ]

    content {
      region_name    = replica.value.name
      propagate_tags = true
    }
  }

  lifecycle {
    prevent_destroy = false
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "viewer_codes_table" {
  name             = "${local.environment_name}-${local.environment.dynamodb_tables.viewer_codes.name}"
  billing_mode     = "PAY_PER_REQUEST"
  hash_key         = "ViewerCode"
  stream_enabled   = true
  stream_view_type = "NEW_IMAGE"
  server_side_encryption {
    enabled = true
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

  dynamic "replica" {
    for_each = [
      for region in local.environment.regions : region
      if region.is_primary != true
    ]

    content {
      region_name    = replica.value.name
      propagate_tags = true
    }
  }


  lifecycle {
    prevent_destroy = false
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "viewer_activity_table" {
  name         = "${local.environment_name}-${local.environment.dynamodb_tables.viewer_activity.name}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ViewerCode"
  range_key    = "Viewed"
  server_side_encryption {
    enabled = true
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

  dynamic "replica" {
    for_each = [
      for region in local.environment.regions : region
      if region.is_primary != true
    ]

    content {
      region_name    = replica.value.name
      propagate_tags = true
    }
  }


  lifecycle {
    prevent_destroy = false
  }

  provider = aws.eu_west_1
}

resource "aws_dynamodb_table" "user_lpa_actor_map" {
  name             = "${local.environment_name}-${local.environment.dynamodb_tables.user_lpa_actor_map.name}"
  billing_mode     = "PAY_PER_REQUEST"
  hash_key         = "Id"
  stream_enabled   = true
  stream_view_type = "NEW_IMAGE"
  server_side_encryption {
    enabled = true
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

  dynamic "replica" {
    for_each = [
      for region in local.environment.regions : region
      if region.is_primary != true
    ]

    content {
      region_name    = replica.value.name
      propagate_tags = true
    }
  }

  lifecycle {
    prevent_destroy = false
  }

  provider = aws.eu_west_1
}
