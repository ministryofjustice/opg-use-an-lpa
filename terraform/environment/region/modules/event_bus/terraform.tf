terraform {
  required_version = "~> 1.9.4"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
      ]
      version = "~> 5.64.0"
    }
  }
}