variable "pagerduty_token" {
  type        = string
  description = "Token for the PagerDuty API"
}

variable "account_mapping" {
  type        = map(string)
  description = "Mapping of account names to account names. This is used so that development can be the default account name for ephemeral environments"
}

variable "lambda_container_version" {
  description = "The version of the lambda container to use"
  type        = string
  default     = "latest"
}

variable "accounts" {
  type = map(
    object({
      account_id             = string
      shared_account_id      = number
      is_production          = bool
      retention_in_days      = number
      pagerduty_service_name = string
      pagerduty_service_id   = string
      opg_metrics = object({
        enabled                     = bool
        api_key_secretsmanager_name = string
        endpoint_url                = string
      })
      dns_firewall = object({
        enabled         = bool
        domains_allowed = list(string)
        domains_blocked = list(string)
      })
      dynamodb_cloudtrail = object({
        enabled            = bool
        trail_name_suffix  = string
        bucket_name_suffix = string
      })
      s3_access_log_bucket_name = string
      regions = map(
        object({
          enabled = bool
        })
      )
    })
  )
  description = "Map of account names to account details"
}
