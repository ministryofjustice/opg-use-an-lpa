locals {
  account    = "${lookup(var.accounts, terraform.workspace)}"
  dns_prefix = "${lookup(var.dns_prefixes, terraform.workspace)}"

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = "${terraform.workspace}"
    owner            = "Katie Gibbs: katie.gibbs@digital.justice.gov.uk"
    is-production    = "${lookup(var.is_production, terraform.workspace)}"
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = "${merge(local.mandatory_moj_tags,local.optional_tags)}"
}

variable "dns_prefixes" {
  type = "map"
}

variable "accounts" {
  type = "map"
}

variable "is_production" {
  type = "map"
}
