terraform {
  required_version = "~> 1.11.0"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
        aws.management,
        aws.us-east-1,
      ]
      version = "~> 5.91.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = ">= 3.0.0"
    }
  }
}
