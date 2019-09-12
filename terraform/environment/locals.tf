locals {
  account_name = "${lookup(var.account_mapping, terraform.workspace, lookup(var.account_mapping, "development"))}"
  account_id   = "${lookup(var.account_ids, local.account_name)}"

  sirius_account_id = "${lookup(var.sirius_account_ids, local.account_name)}"

  dns_namespace_acc = "${terraform.workspace == "production" ? "": "${local.account_name}."}"
  dns_namespace_env = "${local.account_name != "development" ? "": "${terraform.workspace}."}"

  dev_wildcard = "${local.account_name != "development" ? "": "*."}"

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

variable "account_ids" {
  type = "map"
}

variable "is_production" {
  type = "map"
}

variable "account_mapping" {
  type = "map"
}

variable "sirius_account_ids" {
  type = "map"
}

