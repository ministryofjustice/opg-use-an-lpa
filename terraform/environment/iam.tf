module "iam" {
  source = "./modules/iam"

  environment_name = local.environment_name
  default_boundary = local.environment.account_name == "development" ? data.aws_iam_policy.default_boundary[0].arn : null
}
