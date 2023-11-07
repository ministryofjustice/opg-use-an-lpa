resource "aws_security_group" "vpc_endpoints_private" {
  name_prefix = "vpc-endpoint-access-private-subnets-${data.aws_region.current.name}"
  description = "vpc endpoint private sg"
  vpc_id      = aws_default_vpc.default.id
  tags        = { Name = "vpc-endpoint-access-private-subnets-${data.aws_region.current.name}" }
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_security_group_rule" "vpc_endpoints_private_subnet_ingress" {
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  security_group_id = aws_security_group.vpc_endpoints_private.id
  type              = "ingress"
  cidr_blocks       = aws_subnet.private[*].cidr_block
  description       = "Allow Services in Private Subnets of ${data.aws_region.current.name} to connect to VPC Interface Endpoints"

  provider = aws.region
}

resource "aws_security_group_rule" "vpc_endpoints_public_subnet_ingress" {
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  security_group_id = aws_security_group.vpc_endpoints_private.id
  type              = "ingress"
  cidr_blocks       = aws_default_subnet.public[*].cidr_block
  description       = "Allow Services in Public Subnets of ${data.aws_region.current.name} to connect to VPC Interface Endpoints"

  provider = aws.region
}

locals {
  interface_endpoint_dev = toset([
    "ec2",
    "ssm",
    "secretsmanager",
    "logs",
    "ecr.dkr",
    "ecr.api"
  ])
  gateway_endpoint_dev = toset([
    "s3",
    #    "dynamodb"
  ])
  interface_endpoint = toset([
    "ec2"
  ])
  gateway_endpoint = toset([])
}

resource "aws_vpc_endpoint" "private" {
  for_each = var.environment_name == "development" ? local.interface_endpoint_dev : local.interface_endpoint

  vpc_id              = aws_default_vpc.default.id
  service_name        = "com.amazonaws.${data.aws_region.current.name}.${each.value}"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true
  security_group_ids  = aws_security_group.vpc_endpoints_private[*].id
  subnet_ids          = aws_subnet.private[*].id
  tags                = { Name = "${each.value}-private-${data.aws_region.current.name}" }

  provider = aws.region
}

resource "aws_vpc_endpoint" "private-gw" {
  for_each = var.environment_name == "development" ? local.gateway_endpoint_dev : local.gateway_endpoint

  vpc_id            = aws_default_vpc.default.id
  service_name      = "com.amazonaws.${data.aws_region.current.name}.${each.value}"
  vpc_endpoint_type = "Gateway"
  route_table_ids   = aws_route_table.private[*].id
  tags              = { Name = "${each.value}-private-${data.aws_region.current.name}" }

  provider = aws.region
}
