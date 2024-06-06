terraform {
  required_version = "<= 1.8.4"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
      ]
      version = "~> 5.52.0"
    }
  }
}
