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
  policy          = data.aws_iam_policy_document.dynamodb_gateway_endpoint.json
}

data "aws_iam_policy_document" "dynamodb_gateway_endpoint" {
  provider = aws.region
  statement {
    sid     = "Allow-callers-from-specific-account"
    effect  = "Allow"
    actions = ["dynamodb:*"]
    resources = [
      "arn:aws:dynamodb:${var.region_name}:${data.aws_caller_identity.current.account_id}:table/*"
    ]
    principals {
      type        = "AWS"
      identifiers = ["*"]
    }
    condition {
      test     = "StringEquals"
      variable = "aws:PrincipalAccount"
      values   = [data.aws_caller_identity.current.account_id]
    }
  }
}
