terraform {
  required_version = "<= 1.6.3"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
        aws.management,
        aws.us-east-1,
      ]
      version = "~> 5.24.0"
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
