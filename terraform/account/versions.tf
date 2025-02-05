terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.84.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.5.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 3.19.0"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.0.0"
    }
  }
}
