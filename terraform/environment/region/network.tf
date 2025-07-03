# old network
data "aws_vpc" "default" {
  default = "true"

  provider = aws.region
}

data "aws_subnets" "private" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }

  tags = {
    Name = "private"
  }

  provider = aws.region
}

data "aws_subnets" "public" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }

  tags = {
    Name = "public"
  }

  provider = aws.region
}

# firewalled network
data "aws_availability_zones" "available" {
  provider = aws.region
}

data "aws_vpc" "main" {
  filter {
    name   = "tag:application"
    values = [data.aws_default_tags.current.tags.application]
  }
  filter {
    name   = "tag:name"
    values = ["${data.aws_default_tags.current.tags.application}-${data.aws_default_tags.current.tags.account-name}-vpc"]
  }
  provider = aws.region
}

data "aws_subnet" "application" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["application*"]
  }
  provider = aws.region
}

data "aws_subnet" "public" {
  count             = 3
  vpc_id            = data.aws_vpc.main.id
  availability_zone = data.aws_availability_zones.available.names[count.index]

  filter {
    name   = "tag:Name"
    values = ["public*"]
  }
  provider = aws.region
}
