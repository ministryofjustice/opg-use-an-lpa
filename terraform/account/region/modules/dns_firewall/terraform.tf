terraform {
  required_version = "~> 1.10.0"

  required_providers {
    aws = {
      source = "hashicorp/aws"
      configuration_aliases = [
        aws.region,
      ]
      version = "~> 5.80.0"
    }
  }
}
