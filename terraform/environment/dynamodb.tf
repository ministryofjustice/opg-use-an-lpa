resource "aws_dynamodb_table" "actor_lpa_codes_table" {
  name         = "${local.environment}-ActorLpaCodes"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ActorLpaCode"

  attribute {
    name = "ActorLpaCode"
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
  hash_key     = "Email"

  attribute {
    name = "Email"
    type = "S"
  }
  attribute {
    name = "ActivationToken"
    type = "S"
  }

  global_secondary_index {
    name            = "ActivationTokenIndex"
    hash_key        = "ActivationToken"
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

  attribute {
    name = "ViewerCode"
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

resource "aws_dynamodb_table" "viewer_activity_table" {
  name         = "${local.environment}-ViewerActivity"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ViewerCode"
  range_key    = "Viewed"

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

