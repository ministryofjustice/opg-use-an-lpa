terraform {
  required_version = "<= 1.6.3"

  required_providers {
    aws = {
      source = "hashicorp/aws"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = ">= 2.16.0"
    }
  }
}
