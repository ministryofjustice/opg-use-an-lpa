terraform {
  required_version = "~> 1.9.4"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.75.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = ">= 2.16.0"
    }
  }
}
