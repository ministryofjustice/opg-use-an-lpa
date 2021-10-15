terraform {
  required_version = ">= 0.14, <= 1.0.1"

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
      version = "= 3.38.0"
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
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
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
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
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
