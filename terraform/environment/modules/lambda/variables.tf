variable "environment" {
  description = "The environment lambda is being deployed to."
  type        = string
}

variable "memory" {
  description = "The memory to use."
  type        = number
  default     = null
}

variable "image_uri" {
  description = "The image uri in ECR."
  type        = string
  default     = null
}

variable "environment_variables" {
  description = "A map that defines environment variables for the Lambda Function."
  type        = map(string)
  default     = {}
}

variable "lambda_name" {
  description = "A unique name for your Lambda Function"
  type        = string
}

variable "package_type" {
  description = "The Lambda deployment package type."
  type        = string
  default     = "Image"
}

variable "timeout" {
  description = "The amount of time your Lambda Function has to run in seconds."
  type        = number
  default     = 30
}

variable "ecr_arn" {
  description = "The ECR arn for lambda image."
  type        = string
  default     = null
}

variable "kms_key" {
  description = "ARN of KMS key for the lambda log group"
  type        = string
}
