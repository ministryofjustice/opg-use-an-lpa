terraform {
  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "opg-use-my-lpa-environment/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    role_arn       = "arn:aws:iam::311462405659:role/opg-use-an-lpa-ci"
    dynamodb_table = "remote_lock"
  }
}

variable "default_role" {
  default = "opg-use-an-lpa-ci"
}

variable "management_role" {
  default = "opg-use-an-lpa-ci"
}

provider "aws" {
  version = "~> 2.70.0"
  region  = "eu-west-1"

  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  version = "~> 2.70.0"
  region  = "us-east-1"
  alias   = "us-east-1"

  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  version = "~> 2.70.0"
  region  = "eu-west-1"
  alias   = "management"

  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
}

provider "pagerduty" {
  version = "~> 1.7.4"
  token   = var.pagerduty_token
}
