terraform {
  required_version = ">= 1.5.6"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
        aws.management,
      ]
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = ">= 3.0.0"
    }
  }
}

data "aws_vpc" "default" {
  default = "true"

  provider = aws.region
}

data "aws_region" "current" {
  provider = aws.region
}
