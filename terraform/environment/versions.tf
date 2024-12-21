terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.82.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.5.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 3.18.0"
    }
  }
}
