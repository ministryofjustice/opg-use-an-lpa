resource "aws_security_group" "vpc_endpoints_private" {
  provider    = aws.region
  name        = "vpc-endpoint-access-private-subnets"
  description = "VPC Interface Endpoints Security Group"
  vpc_id      = var.vpc_id
  tags        = { Name = "vpc-endpoint-access-private-subnets" }
}

resource "aws_security_group_rule" "vpc_endpoints_private_subnet_ingress" {
  provider          = aws.region
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  security_group_id = aws_security_group.vpc_endpoints_private.id
  type              = "ingress"
  cidr_blocks       = var.application_subnets_cidr_blocks
  description       = "Allow Services in Private Subnets of ${var.region_name} to connect to VPC Interface Endpoints"
}

resource "aws_security_group_rule" "vpc_endpoints_public_subnet_ingress" {
  provider          = aws.region
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  security_group_id = aws_security_group.vpc_endpoints_private.id
  type              = "ingress"
  cidr_blocks       = var.public_subnets_cidr_blocks
  description       = "Allow Services in Public Subnets of ${var.region_name} to connect to VPC Interface Endpoints"
}
