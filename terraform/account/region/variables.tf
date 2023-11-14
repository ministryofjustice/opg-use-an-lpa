variable "account" {
  description = "The account object"
}

variable "account_name" {
  description = "The account name"
}

variable "environment_name" {
  description = "The environment name"
}

variable "lambda_container_version" {
  description = "The version of the lambda container"
}

variable "vpc_flow_logs_iam_role" {
  description = "The IAM role for VPC flow logs"
  type = object({
    arn = string
    id  = string
  })
}
