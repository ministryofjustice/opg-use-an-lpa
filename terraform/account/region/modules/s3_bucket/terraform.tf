terraform {
  required_version = "<= 1.6.3"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
      ]
    }
  }
}
