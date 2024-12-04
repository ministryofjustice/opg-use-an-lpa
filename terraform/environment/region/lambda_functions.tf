data "aws_ecr_repository" "ingestion_repo" {
  name     = "use_an_lpa/ingestion_lambda"
  provider = aws.management
}

module "ingestion_lambda" {
  count                               = var.ingestion_lambda_enabled ? 1 : 0
  source                              = "./modules/lambda_function"
  lambda_name                         = "ingestion-lambda-${var.environment_name_name}"
  working_directory                   = "/"
  image_uri                           = "${data.aws_ecr_repository.ingestion_repo.repository_url}:${var.lambda_container_version}"
  ecr_arn                             = data.aws_ecr_repository.ingestion_repo.arn
  aws_cloudwatch_log_group_kms_key_id = data.aws_kms_alias.cloudwatch_mrk.arn

  providers = {
    aws = aws.region
  }
}
