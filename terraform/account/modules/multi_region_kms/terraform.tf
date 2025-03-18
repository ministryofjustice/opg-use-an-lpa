terraform {
  required_version = "~> 1.11.0"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.primary,
        aws.secondary,
      ]
      version = "~> 5.91.0"
    }
  }
}
