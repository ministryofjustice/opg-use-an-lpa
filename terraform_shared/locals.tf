locals {
  account_name = "${lookup(var.account_mapping, terraform.workspace, lookup(var.account_mapping, "development"))}"
  account      = "${lookup(var.accounts, local.account_name)}"

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = "${terraform.workspace}"
    owner            = "Katie Gibbs: katie.gibbs@digital.justice.gov.uk"
    is-production    = "${lookup(var.is_production, local.account_name)}"
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = "${merge(local.mandatory_moj_tags,local.optional_tags)}"
}

variable "accounts" {
  type = "map"
}

variable "is_production" {
  type = "map"
}
