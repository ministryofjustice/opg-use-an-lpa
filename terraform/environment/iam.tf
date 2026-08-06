module "iam" {
  source = "./modules/iam"

  environment_name = local.environment_name
  default_boundary = data.aws_iam_policy.default_boundary.arn
}
