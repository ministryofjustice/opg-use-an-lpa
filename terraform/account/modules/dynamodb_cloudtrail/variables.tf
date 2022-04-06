variable "trail_name_suffix" {
  description = "trail name to be appended to ddb-cloudtrail-<AWS-REGION>-"
  type        = string
  validation {
    condition     = length(var.trail_name_suffix) < 38
    error_message = "The trail_name_suffix value must be a less than 38 characters in length."
  }
}

variable "bucket_name_suffix" {
  description = "trail name to be appended to ddb.cloudtrail.<AWS-REGION>."
  type        = string
  validation {
    condition     = length(var.bucket_name_suffix) < 38
    error_message = "The bucket_name_suffix value must be a less than 38 characters in length."
  }
}

variable "s3_access_logging_bucket_name" {
  description = "The name of the bucket that will receive the log objects"
  type        = string
}

data "aws_region" "current" {}
