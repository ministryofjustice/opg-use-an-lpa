variable "description" {
  description = "Description of your Lambda Function (or Layer)"
  type        = string
  default     = ""
}

variable "lambda_role_policy_document" {
  description = "The policy JSON for the lambda IAM role. This policy JSON is merged with Logging and ECR access included in the module."
  type        = string
  default     = null
}

variable "command" {
  description = "The CMD for the docker image."
  type        = list(string)
  default     = null
}

variable "entry_point" {
  description = "The ENTRYPOINT for the docker image."
  type        = list(string)
  default     = null
}

variable "environment_variables" {
  description = "A map that defines environment variables for the Lambda Function."
  type        = map(string)
  default     = {}
}

variable "image_uri" {
  description = "The URI for the container image to use"
  type        = string
  default     = null
}
variable "ecr_arn" {
  description = "The ARN for the ECR Repository"
  type        = string
  default     = null
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
  default     = 3
}

variable "working_directory" {
  description = "The working directory for the docker image."
  type        = string
  default     = null
}

variable "aws_cloudwatch_log_group_kms_key_id" {
  type        = string
  description = "The ARN of the KMS Key to use when encrypting log data."
}
