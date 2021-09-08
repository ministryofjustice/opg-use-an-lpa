terraform {
  required_version = ">= 0.14, <= 1.0.1"

  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "opg-use-my-lpa-shared/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    role_arn       = "arn:aws:iam::311462405659:role/opg-use-an-lpa-ci"
    dynamodb_table = "remote_lock"
  }

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 1.4.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 1.7.4"
    }
  }
}

variable "default_role" {
  default = "opg-use-an-lpa-ci"
}

variable "management_role" {
  default = "opg-use-an-lpa-ci"
}

provider "aws" {
  region = "eu-west-1"

  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "management"

  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "shared-development"

  assume_role {
    role_arn     = "arn:aws:iam::679638075911:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "pagerduty" {
  token = var.pagerduty_token
}
