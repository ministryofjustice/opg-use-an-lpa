terraform {
  required_version = "~> 1.11.0"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.us-east-1,
        aws.management,
      ]
      version = "~> 5.94.0"
    }
  }
}
