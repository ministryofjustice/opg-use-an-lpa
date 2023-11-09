terraform {
  required_version = "<= 1.6.2"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.primary,
        aws.secondary,
      ]
    }
  }
}
