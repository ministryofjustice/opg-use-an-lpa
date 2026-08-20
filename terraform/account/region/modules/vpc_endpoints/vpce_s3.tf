resource "aws_vpc_endpoint" "s3" {
  provider          = aws.region
  vpc_id            = var.vpc_id
  service_name      = "com.amazonaws.${var.region_name}.s3"
  route_table_ids   = tolist(var.application_route_tables.ids)
  vpc_endpoint_type = "Gateway"
  tags              = { Name = "s3-private" }
}

resource "aws_vpc_endpoint_policy" "s3" {
  provider        = aws.region
  vpc_endpoint_id = aws_vpc_endpoint.s3.id
  policy          = data.aws_iam_policy_document.s3.json
}

data "aws_iam_policy_document" "s3" {
  source_policy_documents = [
    data.aws_iam_policy_document.allow_account_access.json,
    data.aws_iam_policy_document.s3_bucket_access.json,
  ]
}

data "aws_iam_policy_document" "s3_bucket_access" {
  statement {
    sid     = "Access-to-specific-bucket-only"
    effect  = "Allow"
    actions = ["s3:GetObject"]
    resources = concat([
      "arn:aws:s3:::prod-${var.region_name}-starport-layer-bucket/*",

    ], var.permitted_s3_buckets)
    principals {
      type        = "AWS"
      identifiers = ["*"]
    }
  }
}
