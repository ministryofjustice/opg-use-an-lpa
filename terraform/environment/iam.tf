module "iam" {
  source = "./modules/iam"

  environment_name = local.environment_name
  default_boundary = local.environment.permissions_boundary_enabled ? data.aws_iam_policy.default_boundary[0].arn : null
}
