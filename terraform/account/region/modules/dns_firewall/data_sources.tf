data "aws_vpc" "default" {
  default  = true
  provider = aws.region
}

data "aws_service" "services" {
  for_each   = toset(local.service_id)
  region     = var.region
  service_id = each.value

  provider = aws.region
}

data "aws_vpc" "main" {
  filter {
    name   = "tag:application"
    values = [var.default_tags["application"]]
  }
  filter {
    name   = "tag:Name"
    values = ["${var.default_tags["application"]}-${var.default_tags["environment-name"]}-vpc"]
  }
  provider = aws.region
}
