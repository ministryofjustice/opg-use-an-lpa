variable "pagerduty_token" {
  type = string
}

variable "container_version" {
  type    = string
  default = "latest"
}

variable "admin_container_version" {
  type    = string
  default = "latest"
}

output "container_version" {
  value = var.container_version
}

output "admin_container_version" {
  value = var.admin_container_version
}

output "workspace_name" {
  value = terraform.workspace
}

variable "environments" {
  type = map(
    object({
      account_id   = string
      account_name = string
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
      build_admin                               = bool
      cookie_expires_use                        = number
      cookie_expires_view                       = number
      google_analytics_id_use                   = string
      google_analytics_id_view                  = string
      have_a_backup_plan                        = bool
      is_production                             = bool
      log_retention_in_days                     = number
      logging_level                             = number
      lpa_codes_endpoint                        = string
      lpas_collection_endpoint                  = string
      pagerduty_service_name                    = string
      pagerduty_service_id                      = string
      session_expires_use                       = number
      session_expires_view                      = number
      session_expires_admin                     = number
      session_expiry_warning                    = number
      ship_metrics_queue_enabled                = bool
      sirius_account_id                         = string
      load_balancer_deletion_protection_enabled = bool
      notify_key_secret_name                    = string
      associate_alb_with_waf_web_acl_enabled    = bool
      pdf_container_version                     = string
      deploy_opentelemetry_sidecar              = bool
      fargate_spot                              = bool
      application_flags = object({
        use_legacy_codes_service                                   = bool
        use_older_lpa_journey                                      = bool
        delete_lpa_feature                                         = bool
        allow_older_lpas                                           = bool
        allow_meris_lpas                                           = bool
        save_older_lpa_requests                                    = bool
        dont_send_lpas_registered_after_sep_2019_to_cleansing_team = bool
      })
      dynamodb_tables = object({
        actor_codes = object({
          name = string
        })
        actor_users = object({
          name = string
        })
        viewer_codes = object({
          name = string
        })
        viewer_activity = object({
          name = string
        })
        user_lpa_actor_map = object({
          name = string
        })
      })
    })
  )
}

locals {
  environment_name  = lower(replace(terraform.workspace, "_", "-"))
  environment       = contains(keys(var.environments), local.environment_name) ? var.environments[local.environment_name] : var.environments["default"]
  dns_namespace_acc = local.environment_name == "production" ? "" : "${local.environment.account_name}."
  dns_namespace_env = local.environment.account_name == "production" ? "" : "${local.environment_name}."
  dev_wildcard      = local.environment.account_name == "production" ? "" : "*."
  capacity_provider = local.environment.fargate_spot ? "FARGATE_SPOT" : "FARGATE"

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = local.environment_name
    owner            = "Sarah Mills: sarah.mills@digital.justice.gov.uk"
    is-production    = local.environment.is_production
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
