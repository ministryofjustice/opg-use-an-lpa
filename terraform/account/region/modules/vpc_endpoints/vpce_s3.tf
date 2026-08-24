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
  policy = jsonencode({
    "Version" : "2012-10-17",
    "Statement" : [
      {
        "Sid" : "Access-to-specific-bucket-only",
        "Effect" : "Allow",
        "Principal" : {
          "AWS" : "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
        },
        "Action" : ["s3:GetObject"],
        "Resource" : concat([
          "arn:aws:s3:::prod-${var.region_name}-starport-layer-bucket/*",
        ], var.permitted_s3_buckets)
      }
    ]
  })
}
