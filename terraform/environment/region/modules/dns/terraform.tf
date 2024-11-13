terraform {
  required_version = "~> 1.9.4"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.us-east-1,
        aws.management,
      ]
      version = "~> 5.75.0"
    }
  }
}
