terraform {
  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.eu_west_1,
        aws.eu_west_2
      ]
      version = ">= 6.40.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 3.0"
    }
  }
  required_version = ">= 1.14.3"
}
