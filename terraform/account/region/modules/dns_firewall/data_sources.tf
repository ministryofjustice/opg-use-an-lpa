data "aws_default_tags" "current" {
  provider = aws.region
}

data "aws_region" "current" {
  provider = aws.region
}

data "aws_vpc" "default" {
  default  = true
  provider = aws.region
}

data "aws_service" "services" {
  for_each   = toset(local.service_id)
  region     = data.aws_region.current.name
  service_id = each.value

  provider = aws.region
}

data "aws_vpc" "main" {
  filter {
    name   = "tag:application"
    values = [data.aws_default_tags.current.tags.application]
  }
  filter {
    name   = "tag:name"
    values = ["${data.aws_default_tags.current.tags.application}-${data.aws_default_tags.current.tags.environment-name}-vpc"]
  }
  provider = aws.region
}
