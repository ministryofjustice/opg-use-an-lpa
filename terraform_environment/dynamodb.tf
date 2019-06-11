resource "aws_dynamodb_table" "viewer_codes_table" {
  name         = "${terraform.workspace}-ViewerCodes"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ViewerCode"

  attribute {
    name = "ViewerCode"
    type = "S"
  }

  point_in_time_recovery {
    enabled = true
  }

  tags = "${local.default_tags}"

  lifecycle {
    prevent_destroy = false
  }
}

resource "aws_dynamodb_table" "viewer_activity_table" {
  name         = "${terraform.workspace}-ViewerActivity"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ViewerCode"
  range_key    = "Viewed"

  attribute = [{

    name = "ViewerCode"
    type = "S"
  },
  {
    name = "Viewed"
    type = "S"
  }
  ]

  point_in_time_recovery {
    enabled = true
  }

  tags = "${local.default_tags}"

  lifecycle {
    prevent_destroy = false
  }
}
