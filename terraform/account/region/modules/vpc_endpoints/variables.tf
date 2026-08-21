variable "vpc_id" {
  description = "id of the VPC to create interface endpoints in"
  type        = string
}

variable "application_subnets_cidr_blocks" {
  description = "application subnet CIDR blocks"
  type        = any
}

variable "application_subnets_id" {
  description = "application subnet CIDR blocks"
  type        = any
}

variable "public_subnets_cidr_blocks" {
  description = "public subnet CIDR blocks"
  type        = any
}

variable "application_route_tables" {
  type = any
}

variable "management_account_id" {
  type = string
}

variable "execute_api_account_ids" {
  description = "AWS account IDs whose API Gateway resources may be called through the execute-api VPC endpoint"
  type        = list(string)
}

variable "permitted_s3_buckets" {
  type        = list(string)
  default     = []
  description = "S3 buckets permitted through the S3 VPC endpoint"
}

variable "region_name" {
  description = "The aws region"
  type        = string
}
