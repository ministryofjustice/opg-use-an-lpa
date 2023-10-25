locals {
  availability_zones = [
    "eu-west-1a",
    "eu-west-1b",
    "eu-west-1c",
  ]
}
resource "aws_default_vpc" "default" {
  tags = { "Name" = "default" }

  provider = aws.region
}

data "aws_availability_zones" "default" {
  provider = aws.region
}

# TODO: Remove this once the above data source has been put into state
resource "aws_key_pair" "foo" {
  count      = 3
  key_name   = "temporary-testing-keypair-${element(data.aws_availability_zones.default.names, count.index)}"
  public_key = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQD3F6tyPEFEzV0LX3X8BsXdMsQz1x2cEikKDEY0aIj41qgxMCP/iteneqXSIFZBp5vizPvaoIR3Um9xK7PGoW8giupGn+EPuxIA4cDM4vzOqOkiMPhz5XK0whEjkVzTo4+S0puvDZuwIsdiW9mxhJc7tgBNL0cYlWSYVkz4G/fslNfRPW5mYAM49f4fhtxPb5ok4Q2Lg9dPKVHO/Bgeu5woMc7RY0p1ej6D4CKFE6lymSDJpW0YHX/wqE9+cfEauh7xZcG0q9t2ta6F6fmX0agvpFyZo8aFbXeUBr7osSCJNgvavWbM/06niWrOvYX2xwWdhXmXSrbX8ZbabVohBK41 temporary-testing-keypair"
}

#TODO: Fix this by changing availability_zone to a data source
resource "aws_default_subnet" "public" {
  count             = 3
  availability_zone = local.availability_zones[count.index]
  # availability_zone       = data.aws_availability_zones.default.names[count.index]
  map_public_ip_on_launch = false
  tags                    = { "Name" = "public" }

  provider = aws.region
}

#TODO: Fix this by changing availability_zone to a data source
resource "aws_subnet" "private" {
  count             = 3
  cidr_block        = cidrsubnet(aws_default_vpc.default.cidr_block, 4, count.index + 3)
  vpc_id            = aws_default_vpc.default.id
  availability_zone = local.availability_zones[count.index]
  # availability_zone       = element(data.aws_availability_zones.default.names, count.index)
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
  route_table_id = element(aws_route_table.private.*.id, count.index)
  subnet_id      = element(aws_subnet.private.*.id, count.index)

  provider = aws.region
}

resource "aws_nat_gateway" "nat" {
  count         = 3
  allocation_id = element(aws_eip.nat.*.id, count.index)
  subnet_id     = element(aws_default_subnet.public.*.id, count.index)

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
  route_table_id         = element(aws_route_table.private.*.id, count.index)
  destination_cidr_block = "0.0.0.0/0"
  nat_gateway_id         = element(aws_nat_gateway.nat.*.id, count.index)

  provider = aws.region
}


resource "aws_default_security_group" "default" {
  vpc_id = aws_default_vpc.default.id

  provider = aws.region
}
