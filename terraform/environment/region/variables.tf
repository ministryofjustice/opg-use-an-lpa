# Many of these variables are temporary and will be removed once the relevant region specific resources are moved to the region module.
# E.g. dynamodb_tables will no longer be needed once the DynamoDB tables are moved to the region module.

variable "alb_tg_arns" {
  description = "Map of ALB ARNs to be used by the ECS services."

  type = map(object({
    arn  = string
    name = string
  }))

}

variable "autoscaling" {
  description = "The min and max number of instances to run for each ECS service."

  type = map(object({
    minimum = number
    maximum = number
  }))
}

variable "application_logs_name" {
  description = "The name of the CloudWatch Logs group to send application logs to."

  type = string
}

variable "environment_name" {
  description = "The name of the environment"

  type = string
}

variable "dynamodb_tables" {
  description = "The DynamoDB tables to use."

  type = map(object({
    name = string
    arn  = string
  }))
}

variable "cognito_user_pool_id" {
  description = "The Cognito User Pool ID to use for authentication to the admin interface."

  type = string
}

variable "route_53_fqdns" {
  description = "The FQDNs to use for the Route 53 records."

  type = map(string)
}

variable "actor_loadbalancer_security_group_id" {
  description = "The ID of the ALB security group for actor service."

  type = string
}

variable "viewer_loadbalancer_security_group_id" {
  description = "The ID of the ALB security group for viewer service."

  type = string
}

variable "admin_loadbalancer_security_group_id" {
  description = "The ID of the ALB security group for admin service."

  type = string
}

variable "container_version" {
  description = "The image tag to use for the containers."
  type        = string
}

variable "admin_container_version" {
  description = "The image tag to use for the admin container."
  type        = string
}

variable "notify_key_secret_name" {
  description = "The name of the secret containing the Notify API key."
  type        = string
}

variable "feature_flags" {
  # Each feature flag is a key-value pair where the key is the name of the feature flag and the value is the value of the feature flag.
  description = "The feature flags to use."
  type        = map(string)
}

variable "ecs_task_roles" {
  description = "The ECS task roles to use."
  type = map(object({
    name = string
    arn  = string
    id   = string
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

variable "lpa_codes_endpoint" {
  description = "The endpoint to use for LPA codes."
  type        = string
}

variable "iap_images_endpoint" {
  description = "The endpoint to use for IAP images."
  type        = string
}

variable "lpas_collection_endpoint" {
  description = "The endpoint to use for LPAs collection."
  type        = string
}

variable "logging_level" {
  description = "The logging level to use for the applications."
  type        = string
}

variable "parameter_store_arns" {
  description = "The ARNs of the Parameter Store parameters to use."
  type        = list(string)
}

variable "sirius_account_id" {
  description = "The AWS ID of the Sirius account."
  type        = string
}

variable "admin_cognito_user_pool_domain_name" {
  description = "The domain name of the Cognito User Pool to use for the admin interface."
  type        = string
}

variable "capacity_provider" {
  description = "The capacity provider to use for the ECS services."
  type        = string
}

variable "aws_service_discovery_service" {
  description = "The AWS Service Discovery service to use."
  type = object({
    id   = string
    arn  = string
    name = string
  })
}

variable "session_expires_view" {
  description = "The number of seconds before the session expires for the viewer service."
  type        = number
}

variable "cookie_expires_view" {
  description = "The number of seconds before the cookie expires for the viewer service."
  type        = number
}

variable "google_analytics_id_view" {
  description = "The Google Analytics ID to use for the viewer service."
  type        = string
}

variable "google_analytics_id_use" {
  description = "The Google Analytics ID to use for the use service."
  type        = string
}

variable "cookie_expires_use" {
  description = "The number of seconds before the cookie expires for the use service."
  type        = string
}

variable "session_expiry_warning" {
  description = "The number of seconds before the session expires to show the warning for the viewer service."
  type        = string
}

variable "session_expires_use" {
  description = "The number of seconds before the session expires for the use service."
  type        = string
}

variable "pdf_container_version" {
  description = "The image tag to use for the PDF container."
  type        = string
}