resource "aws_dynamodb_table" "codes-table" {
  name         = "${terraform.workspace}-ViewerCodes"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "ViewerCode"

  attribute {
    name = "Code"
    type = "S"
  }

  server_side_encryption {
    enabled = false
  }

  point_in_time_recovery {
    enabled = true
  }

  tags = "${local.default_tags}"

  lifecycle {
    prevent_destroy = true
  }
}
