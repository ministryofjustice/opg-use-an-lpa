variable "pagerduty_token" {
}

variable "account_mapping" {
  type = map(string)
}

variable "lambda_container_version" {
  type    = string
  default = "latest"
}
variable "accounts" {
  type = map(
    object({
      account_id             = string
      shared_account_id      = number
      is_production          = bool
      retention_in_days      = number
      pagerduty_service_name = string
      opg_metrics = object({
        enabled                     = bool
        api_key_secretsmanager_name = string
        endpoint_url                = string
      })
    })
  )
}

locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account      = var.accounts[local.account_name]
  environment  = lower(terraform.workspace)

  dns_namespace_acc = local.environment == "production" ? "" : "${local.account_name}."
  dns_namespace_env = local.account_name == "production" ? "" : "${local.environment}."
  dev_wildcard      = local.account_name == "production" ? "" : "*."

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = local.environment
    owner            = "Sarah Mills: sarah.mills@digital.justice.gov.uk"
    is-production    = local.account.is_production
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
