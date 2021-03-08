variable "pagerduty_token" {
}

variable "account_mapping" {
  type = map(string)
}

variable "accounts" {
  type = map(
    object({
      account_id                 = string
      is_production              = bool
      retention_in_days          = number
      pagerduty_service_name     = string
      ship_metrics_queue_enabled = bool
    })
  )
}

locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account      = var.accounts[local.account_name]
  environment  = lower(terraform.workspace)

  dns_namespace_acc = local.environment == "production" ? "" : "${local.account_name}."
  dev_wildcard      = local.account_name == "production" ? "" : "*."

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = local.environment
    owner            = "Katie Gibbs: katie.gibbs@digital.justice.gov.uk"
    is-production    = local.account.is_production
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
