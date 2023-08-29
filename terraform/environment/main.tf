module "iam" {
  source = "./modules/iam"

  environment_name = local.environment_name
}