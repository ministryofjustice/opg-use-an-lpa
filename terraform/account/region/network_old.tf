locals {
}
resource "aws_default_vpc" "default" {
  tags = { "Name" = "default" }

  provider = aws.region
}

data "aws_availability_zones" "default" {
  provider = aws.region
}

resource "aws_default_subnet" "public" {
  count                   = 3
  availability_zone       = element(data.aws_availability_zones.default.names, count.index)
  map_public_ip_on_launch = false
  tags                    = { "Name" = "public" }

  provider = aws.region
}

resource "aws_subnet" "private" {
  count                   = 3
  cidr_block              = cidrsubnet(aws_default_vpc.default.cidr_block, 4, count.index + 3)
  vpc_id                  = aws_default_vpc.default.id
  availability_zone       = element(data.aws_availability_zones.default.names, count.index)
  map_public_ip_on_launch = false
  tags                    = { "Name" = "private" }

  provider = aws.region
}

resource "aws_eip" "nat" {
  count = 3
  tags  = { "Name" = "nat" }

  provider = aws.region
}

data "aws_internet_gateway" "default" {
  filter {
    name   = "attachment.vpc-id"
    values = [aws_default_vpc.default.id]
  }

  provider = aws.region
}

resource "aws_route_table_association" "private" {
  count          = 3
  route_table_id = element(aws_route_table.private[*].id, count.index)
  subnet_id      = element(aws_subnet.private[*].id, count.index)

  provider = aws.region
}

resource "aws_nat_gateway" "nat" {
  count         = 3
  allocation_id = element(aws_eip.nat[*].id, count.index)
  subnet_id     = element(aws_default_subnet.public[*].id, count.index)

  tags = { "Name" = "nat" }

  provider = aws.region
}

resource "aws_default_route_table" "default" {
  default_route_table_id = aws_default_vpc.default.default_route_table_id

  tags = { "Name" = "default" }

  provider = aws.region
}

resource "aws_route_table" "private" {
  count  = 3
  vpc_id = aws_default_vpc.default.id

  tags = { "Name" = "private" }

  provider = aws.region
}

resource "aws_route" "default" {
  route_table_id         = aws_default_route_table.default.id
  destination_cidr_block = "0.0.0.0/0"
  gateway_id             = data.aws_internet_gateway.default.id

  provider = aws.region
}

resource "aws_route" "private" {
  count                  = 3
  route_table_id         = element(aws_route_table.private[*].id, count.index)
  destination_cidr_block = "0.0.0.0/0"
  nat_gateway_id         = element(aws_nat_gateway.nat[*].id, count.index)

  provider = aws.region
}


resource "aws_default_security_group" "default" {
  vpc_id = aws_default_vpc.default.id

  provider = aws.region
}

resource "aws_flow_log" "vpc_flow_logs" {
  iam_role_arn    = var.vpc_flow_logs_iam_role.arn
  log_destination = aws_cloudwatch_log_group.vpc_flow_logs.arn
  traffic_type    = "ALL"
  vpc_id          = aws_default_vpc.default.id

  provider = aws.region
}

resource "aws_cloudwatch_log_group" "vpc_flow_logs" {
  name              = "vpc_flow_logs-${data.aws_region.current.region}"
  retention_in_days = 400
  kms_key_id        = data.aws_kms_alias.cloudwatch_mrk.arn

  provider = aws.region
}

# Kept around to avoid losing logs after switching to region-specific flow logs group.
# This can be deleted 400 days after the creation of aws_cloudwatch_log_group.vpc_flow_logs.
resource "aws_cloudwatch_log_group" "old_vpc_flow_logs" {
  count             = data.aws_region.current.region == "eu-west-1" ? 1 : 0
  name              = "vpc_flow_logs"
  retention_in_days = 400
  kms_key_id        = data.aws_kms_alias.cloudwatch_mrk.arn

  provider = aws.region
}
