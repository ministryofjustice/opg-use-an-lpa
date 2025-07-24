terraform {


  backend "s3" {
    bucket  = "opg.terraform.state"
    key     = "opg-use-my-lpa-shared/terraform.tfstate"
    encrypt = true
    region  = "eu-west-1"
    assume_role = {
      role_arn = "arn:aws:iam::311462405659:role/opg-use-an-lpa-ci"
    }
    dynamodb_table = "remote_lock"
  }
}

variable "default_role" {
  default     = "opg-use-an-lpa-ci"
  type        = string
  description = "The default role to assume for the AWS providers"
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
  region = "eu-west-1"
  alias  = "eu_west_1"
  default_tags {
    tags = local.default_tags
  }

  assume_role {
    role_arn     = "arn:aws:iam::${local.account.account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-2"
  alias  = "eu_west_2"
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
    role_arn     = "arn:aws:iam::311462405659:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-2"
  alias  = "management_eu_west_2"
  default_tags {
    tags = local.default_tags
  }

  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "aws" {
  region = "eu-west-1"
  alias  = "management_eu_west_1"
  default_tags {
    tags = local.default_tags
  }

  assume_role {
    role_arn     = "arn:aws:iam::311462405659:role/${var.default_role}"
    session_name = "terraform-session"
  }
}



provider "aws" {
  region = "eu-west-1"
  alias  = "shared"
  default_tags {
    tags = local.default_tags
  }

  assume_role {
    role_arn     = "arn:aws:iam::${local.account.shared_account_id}:role/${var.default_role}"
    session_name = "terraform-session"
  }
}

provider "pagerduty" {
  token = var.pagerduty_token
}
