variable "account_mapping" {
  type = map
}

variable "container_version" {
  type    = string
  default = "latest"
}

variable "accounts" {
  type = map(
    object({
      account_id           = string
      is_production        = bool
      sirius_account_id    = string
      api_gateway_endpoint = string
      session_expires_view = number
      session_expires_use  = number
      logging_level        = number
    })
  )
}

locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account      = var.accounts[local.account_name]
  environment  = lower(terraform.workspace)

  account_id           = local.account.account_id
  sirius_account_id    = local.account.sirius_account_id
  api_gateway_endpoint = local.account.api_gateway_endpoint


  dns_namespace_acc = local.environment == "production" ? "" : "${local.account_name}."
  dns_namespace_env = local.account_name == "production" ? "" : "${local.environment}."
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
