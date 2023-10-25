module "eu_west_1" {
  source = "./region"

  account                  = local.account
  account_name             = local.account_name
  environment_name         = local.environment
  lambda_container_version = var.lambda_container_version

  depends_on = [
    module.cloudwatch_mrk,
  ]

  providers = {
    aws.region     = aws.eu_west_1
    aws.management = aws.management
    aws.shared     = aws.shared
  }
}
