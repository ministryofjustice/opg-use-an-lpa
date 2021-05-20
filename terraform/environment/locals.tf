variable "pagerduty_token" {
}

variable "account_mapping" {
  type = map(any)
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
      account_id = string
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
      build_admin                = bool
      cookie_expires_use         = number
      cookie_expires_view        = number
      google_analytics_id_use    = string
      google_analytics_id_view   = string
      have_a_backup_plan         = bool
      is_production              = bool
      log_retention_in_days      = number
      logging_level              = number
      lpa_codes_endpoint         = string
      lpas_collection_endpoint   = string
      pagerduty_service_name     = string
      session_expires_use        = number
      session_expires_view       = number
      session_expiry_warning     = number
      ship_metrics_queue_enabled = bool
      sirius_account_id          = string
      use_legacy_codes_service   = bool
      use_older_lpa_journey      = bool
      delete_lpa_feature         = bool
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
