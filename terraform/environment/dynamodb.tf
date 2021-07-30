resource "aws_dynamodb_table" "actor_codes_table" {
  name         = "${local.environment}-ActorCodes"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ActorCode"
  # server_side_encryption {
  #   enabled = true
  # }

  attribute {
    name = "ActorCode"
    type = "S"
  }

  point_in_time_recovery {
    enabled = true
  }

  tags = local.default_tags

  lifecycle {
    prevent_destroy = false
  }
}

resource "aws_dynamodb_table" "actor_users_table" {
  name         = "${local.environment}-ActorUsers"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "Id"
  # server_side_encryption {
  #   enabled = true
  # }

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

  tags = local.default_tags

  lifecycle {
    prevent_destroy = false
  }
}

resource "aws_dynamodb_table" "viewer_codes_table" {
  name         = "${local.environment}-ViewerCodes"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ViewerCode"
  # server_side_encryption {
  #   enabled = true
  # }

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

  tags = local.default_tags

  lifecycle {
    prevent_destroy = false
  }
}

resource "aws_dynamodb_table" "viewer_activity_table" {
  name         = "${local.environment}-ViewerActivity"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ViewerCode"
  range_key    = "Viewed"
  # server_side_encryption {
  #   enabled = true
  # }

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

  tags = local.default_tags

  lifecycle {
    prevent_destroy = false
  }
}

resource "aws_dynamodb_table" "user_lpa_actor_map" {
  name         = "${local.environment}-UserLpaActorMap"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "Id"
  # server_side_encryption {
  #   enabled = true
  # }

  attribute {
    name = "Id"
    type = "S"
  }

  attribute {
    name = "UserId"
    type = "S"
  }

  global_secondary_index {
    name            = "UserIndex"
    hash_key        = "UserId"
    projection_type = "ALL"
  }

  ttl {
    attribute_name = "ActivateBy"
    enabled        = true
  }

  point_in_time_recovery {
    enabled = true
  }

  tags = local.default_tags

  lifecycle {
    prevent_destroy = false
  }
}
