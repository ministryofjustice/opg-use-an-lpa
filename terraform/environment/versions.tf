terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 2.70.0"
    }
    local = {
      source = "hashicorp/local"
    }
    pagerduty = {
      source  = "terraform-providers/pagerduty"
      version = "~> 1.7.4"
    }
  }
  required_version = ">= 0.13"
}
