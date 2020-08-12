terraform {
  required_providers {
    aws = {
      source = "hashicorp/aws"
    }
    local = {
      source = "hashicorp/local"
    }
    pagerduty = {
      source = "terraform-providers/pagerduty"
    }
  }
  required_version = ">= 0.13"
}
