terraform {
  required_version = ">= 1.5.6"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
      ]
    }
  }
}
