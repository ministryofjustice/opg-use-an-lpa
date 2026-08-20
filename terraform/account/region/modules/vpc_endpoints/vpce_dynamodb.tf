resource "aws_vpc_endpoint" "dynamodb" {
  provider          = aws.region
  vpc_id            = var.vpc_id
  service_name      = "com.amazonaws.${var.region_name}.dynamodb"
  route_table_ids   = tolist(var.application_route_tables.ids)
  vpc_endpoint_type = "Gateway"
  tags              = { Name = "dynamodb-private" }
}

resource "aws_vpc_endpoint_policy" "dynamodb" {
  provider        = aws.region
  vpc_endpoint_id = aws_vpc_endpoint.dynamodb.id
  policy          = data.aws_iam_policy_document.allow_account_access.json
}
