variable "pagerduty_token" {
}

variable "account_mapping" {
  type = map
}

variable "container_version" {
  type    = string
  default = "latest"
}

output "container_version" {
  value = var.container_version
}

output "workspace_name" {
  value = terraform.workspace
}

variable "accounts" {
  type = map(
    object({
      account_id               = string
      is_production            = bool
      sirius_account_id        = string
      lpas_collection_endpoint = string
      lpa_codes_endpoint       = string
      session_expires_view     = number
      session_expires_use      = number
      session_expiry_warning   = number
      cookie_expires_view      = number
      cookie_expires_use       = number
      google_analytics_id_view = string
      google_analytics_id_use  = string
      logging_level            = number
      pagerduty_service_name   = string
      use_legacy_codes_service = bool
      log_retention_in_days    = number
      have_a_backup_plan       = bool
      autoscaling = object({
        use = object({
          minimum = number
          maximum = number
        })
        view = object({
          minimum = number
          maximum = number
        })
        api = object({
          minimum = number
          maximum = number
        })
        pdf = object({
          minimum = number
          maximum = number
        })
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
    owner            = "Katie Gibbs: katie.gibbs@digital.justice.gov.uk"
    is-production    = local.account.is_production
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
