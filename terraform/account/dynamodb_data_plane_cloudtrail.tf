module "production_dynamodb_cloudtrail" {
  source                        = "./modules/dynamodb_cloudtrail"
  count                         = local.account.dynamodb_cloudtrail.enabled ? 1 : 0
  trail_name_suffix             = local.account.dynamodb_cloudtrail.trail_name_suffix
  bucket_name_suffix            = local.account.dynamodb_cloudtrail.bucket_name_suffix
  s3_access_logging_bucket_name = "${local.account.s3_access_log_bucket_name}-${local.region}"
  default_boundary              = data.aws_iam_policy.default_boundary.arn
  region_name                   = local.region
}
