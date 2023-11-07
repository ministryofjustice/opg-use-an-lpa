variable "account_name" {
  description = "The name of the AWS account."
  type        = string
}

variable "acm_certificate_arns" {
  description = "The ARNs of the ACM certificates to use."
  type = object({
    use                = string
    view               = string
    admin              = string
    public_facing_use  = string
    public_facing_view = string
  })
}

variable "admin_cognito" {
  description = "Settings for the AWS Cognito to use for the admin interface."
  type = object({
    id                          = string
    user_pool_id                = string
    user_pool_domain_name       = string
    user_pool_client_secret     = string
    user_pool_id_token_validity = string
  })
  sensitive = true
}

variable "admin_container_version" {
  description = "The image tag to use for the admin container."
  type        = string
}

variable "associate_alb_with_waf_web_acl_enabled" {
  description = "Whether or not to associate the ALBs with the WAF web ACL."
  type        = bool
}

variable "autoscaling" {
  description = "The min and max number of instances to run for each ECS service."
  type = map(object({
    minimum = number
    maximum = number
  }))
}

variable "capacity_provider" {
  description = "The capacity provider to use for the ECS services."
  type        = string
}

variable "container_version" {
  description = "The image tag to use for the containers."
  type        = string
}

variable "cookie_expires_use" {
  description = "The number of seconds before the cookie expires for the use service."
  type        = string
}

variable "cookie_expires_view" {
  description = "The number of seconds before the cookie expires for the viewer service."
  type        = number
}

variable "dns_namespace_env" {
  description = "The environment to use for the DNS namespace."
  type        = string
}

variable "dynamodb_tables" {
  description = "The DynamoDB tables to use."
  type = map(object({
    name = string
    arn  = string
  }))
}

variable "ecs_execution_role" {
  description = "The ECS execution role to use."
  type = object({
    name = string
    arn  = string
    id   = string
  })
}

variable "ecs_task_roles" {
  description = "The ECS task roles to use."
  type = map(object({
    name = string
    arn  = string
    id   = string
  }))
}

variable "environment_name" {
  description = "The name of the environment"
  type        = string
}

variable "feature_flags" {
  description = "The feature flags to use."
  type        = map(string)
}

variable "google_analytics_id_use" {
  description = "The Google Analytics ID to use for the use service."
  type        = string
}

variable "google_analytics_id_view" {
  description = "The Google Analytics ID to use for the viewer service."
  type        = string
}

variable "iap_images_endpoint" {
  description = "The endpoint to use for IAP images."
  type        = string
}

variable "logging_level" {
  description = "The logging level to use for the applications."
  type        = string
}

variable "log_retention_days" {
  description = "The number of days to retain logs for."
  type        = number
}

variable "lpa_codes_endpoint" {
  description = "The endpoint to use for LPA codes."
  type        = string
}

variable "lpas_collection_endpoint" {
  description = "The endpoint to use for LPAs collection."
  type        = string
}

variable "load_balancer_deletion_protection_enabled" {
  description = "Whether or not deletion protection should be enabled for the load balancers."
  type        = bool
  default     = false
}

variable "moj_sites" {
  description = "A list of MOJ IP addresses used by security groups to allow access to the admin interface and non-production environments."
  type        = list(string)
}

variable "notify_key_secret_name" {
  description = "The name of the secret containing the Notify API key."
  type        = string
}

variable "pagerduty_service_id" {
  description = "The ID of the PagerDuty service to use."
  type        = string
}

variable "parameter_store_arns" {
  description = "The ARNs of the Parameter Store parameters to use."
  type        = list(string)
}

variable "pdf_container_version" {
  description = "The image tag to use for the PDF container."
  type        = string
}

variable "public_access_enabled" {
  description = "Whether or not the front ECS services should be publicly accessible via the ALBs."
  type        = bool
  default     = false
}

variable "regions" {
  description = "Information about which regions are being used"
  type = map(object({
    is_primary = bool
    is_active  = bool
  }))

  validation {
    condition     = length([for region in keys(var.regions) : region if var.regions[region].is_primary]) == 1
    error_message = "One (and only one) region must be marked as primary"
  }
}

variable "session_expires_use" {
  description = "The number of seconds before the session expires for the use service."
  type        = string
}

variable "session_expires_view" {
  description = "The number of seconds before the session expires for the viewer service."
  type        = number
}

variable "session_expiry_warning" {
  description = "The number of seconds before the session expires to show the warning for the viewer service."
  type        = string
}

variable "ship_metrics_queue_enabled" {
  description = "Whether or not to forward metrics to opg-metrics"
  type        = bool
  default     = false
}

variable "sirius_account_id" {
  description = "The AWS ID of the Sirius account."
  type        = string
}
