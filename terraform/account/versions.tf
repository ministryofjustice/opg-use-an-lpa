terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.75.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.5.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 3.17.0"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.0.0"
    }
  }
}
