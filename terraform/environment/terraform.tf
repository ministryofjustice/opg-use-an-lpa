terraform {
  required_version = "<= 1.6.2"

  backend "s3" {
    bucket         = "opg.terraform.state"
    key            = "opg-use-my-lpa-environment/terraform.tfstate"
    encrypt        = true
    region         = "eu-west-1"
    role_arn       = "arn:aws:iam::311462405659:role/opg-use-an-lpa-ci"
    dynamodb_table = "remote_lock"
  }

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "4.67.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.4.0"
    }
    pagerduty = {
      source  = "PagerDuty/pagerduty"
      version = "~> 3.1.0"
    }
  }
}

variable "default_role" {
  type    = string
  default = "opg-use-an-lpa-ci"
}

variable "management_role" {
  type    = string
  default = "opg-use-an-lpa-ci"
}

provider "aws" {
  region = "eu-west-1"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.environment.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "eu_west_1"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.environment.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "us-east-1"
  alias  = "us-east-1"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::${local.environment.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "management"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.management_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "identity"
  default_tags {
    tags = local.default_tags
  }
  assume_role {
    role_arn     = "arn:aws:iam::631181914621:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "pagerduty" {
  token = var.pagerduty_token
}

data "aws_region" "current" {}
