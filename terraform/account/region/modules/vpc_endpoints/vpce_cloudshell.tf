locals {
  cloudshell_endpoints = toset([
    "ecs-agent",
    "ecs-telemetry",
    "ecs",
    "ssmmessages",
  ])

  # CodeCatalyst VPC endpoints are only available in eu-west-1
  codecatalyst_endpoints = var.region_name == "eu-west-1" ? toset([
    "codecatalyst.packages",
    "codecatalyst.git",
  ]) : toset([])
}

resource "aws_vpc_endpoint" "cloudshell" {
  provider            = aws.region
  for_each            = local.cloudshell_endpoints
  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${var.region_name}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  tags                = { Name = "cloudshell-${each.value}-private" }
}

resource "aws_vpc_endpoint_policy" "cloudshell" {
  provider = aws.region
  for_each = local.cloudshell_endpoints

  vpc_endpoint_id = aws_vpc_endpoint.cloudshell[each.value].id
  policy = jsonencode({
    "Version" : "2012-10-17",
    "Statement" : [
      {
        "Sid" : "AllowAll",
        "Effect" : "Allow",
        "Principal" : {
          "AWS" : "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
        },
        "Action" : ["ecs:*", "ssmmessages:*"],
        "Resource" : "*"
      }
    ]
  })
}

resource "aws_vpc_endpoint" "codecatalyst" {
  provider            = aws.region
  for_each            = local.codecatalyst_endpoints
  vpc_id              = var.vpc_id
  service_name        = "com.amazonaws.${var.region_name}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  # CodeCatalyst endpoints only support the AWS-managed full-access policy
  tags = { Name = "cloudshell-${each.value}-private" }
}

resource "aws_vpc_endpoint" "cloudshell_codecatalyst_global" {
  provider            = aws.region
  count               = var.region_name == "eu-west-1" ? 1 : 0
  vpc_id              = var.vpc_id
  service_name        = "aws.api.global.codecatalyst"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = var.application_subnets_id
  # CodeCatalyst endpoints only support the AWS-managed full-access policy
  tags = { Name = "cloudshell-aws.api.global.codecatalyst-private" }
}
