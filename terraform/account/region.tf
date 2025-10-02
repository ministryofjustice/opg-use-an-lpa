module "eu_west_1" {
  source = "./region"

  count = local.account.regions.eu_west_1.enabled ? 1 : 0

  account                  = local.account
  account_name             = local.account_name
  environment_name         = local.environment
  lambda_container_version = var.lambda_container_version
  network_cidr_block       = "10.162.0.0/16"

  depends_on = [
    module.cloudwatch_mrk,
  ]

  providers = {
    aws.region     = aws.eu_west_1
    aws.management = aws.management_eu_west_1
    aws.shared     = aws.shared
  }
}

moved {
  from = module.eu_west_1
  to   = module.eu_west_1[0]
}

module "eu_west_2" {
  source = "./region"

  count = local.account.regions.eu_west_2.enabled ? 1 : 0

  account                  = local.account
  account_name             = local.account_name
  environment_name         = local.environment
  lambda_container_version = var.lambda_container_version
  network_cidr_block       = "10.162.0.0/16"

  depends_on = [
    module.cloudwatch_mrk,
  ]

  providers = {
    aws.region     = aws.eu_west_2
    aws.management = aws.management_eu_west_2
    aws.shared     = aws.shared
  }
}
