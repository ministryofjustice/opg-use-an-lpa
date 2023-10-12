# Many of these variables are temporary and will be removed once the relevant region specific resources are moved to the region module.
# E.g. dynamodb_tables will no longer be needed once the DynamoDB tables are moved to the region module.

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

variable "application_logs_name" {
  description = "The name of the CloudWatch Logs group to send application logs to."
  type        = string
}

variable "autoscaling" {
  description = "The min and max number of instances to run for each ECS service."
  type = map(object({
    minimum = number
    maximum = number
  }))
}

variable "aws_service_discovery_service" {
  description = "The AWS Service Discovery service to use."
  type = object({
    id   = string
    arn  = string
    name = string
  })
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

variable "route_53_fqdns" {
  description = "The FQDNs to use for the Route 53 records."

  type = map(string)
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

variable "sirius_account_id" {
  description = "The AWS ID of the Sirius account."
  type        = string
}
