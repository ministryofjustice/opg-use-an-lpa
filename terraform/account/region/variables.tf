variable "account" {
  description = "The account object"
  type = object({
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
  })
}

variable "account_name" {
  description = "The account name"
  type        = string
}

variable "environment_name" {
  description = "The environment name"
  type        = string
}

variable "lambda_container_version" {
  description = "The version of the lambda container"
  type        = string
}

variable "vpc_flow_logs_iam_role" {
  description = "The IAM role for VPC flow logs"
  type = object({
    arn = string
    id  = string
  })
}

variable "network_cidr_block" {
  type        = string
  description = "The IPv4 CIDR block for the VPC. CIDR can be explicitly set or it can be derived from IPAM using ipv4_netmask_length."
}
