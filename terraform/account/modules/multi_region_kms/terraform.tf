terraform {
  required_version = "<= 1.8.4"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.primary,
        aws.secondary,
      ]
      version = "~> 5.64.0"
    }
  }
}
