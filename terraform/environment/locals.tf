locals {
  account_name = lookup(
    var.account_mapping,
    terraform.workspace,
    var.account_mapping["development"],
  )
  account_id  = var.account_ids[local.account_name]
  environment = terraform.workspace

  sirius_account_id = var.sirius_account_ids[local.account_name]

  dns_namespace_acc = terraform.workspace == "production" ? "" : "${local.account_name}."
  dns_namespace_env = local.account_name == "production" ? "" : "${terraform.workspace}."

  dev_wildcard = local.account_name == "production" ? "" : "*."

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = terraform.workspace
    owner            = "Katie Gibbs: katie.gibbs@digital.justice.gov.uk"
    is-production    = var.is_production[local.account_name]
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}

variable "account_ids" {
  type = map(string)
}

variable "is_production" {
  type = map(string)
}

variable "account_mapping" {
  type = map(string)
}

variable "sirius_account_ids" {
  type = map(string)
}

