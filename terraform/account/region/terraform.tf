terraform {
  required_version = "~> 1.12.0"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
        aws.management,
        aws.shared,
      ]
      version = "~> 5.98.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = ">= 2.16.0"
    }
  }
}
