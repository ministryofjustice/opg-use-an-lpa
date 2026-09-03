locals {
  environment_name                           = lower(replace(terraform.workspace, "_", "-"))
  environment                                = contains(keys(var.environments), local.environment_name) ? var.environments[local.environment_name] : var.environments["default"]
  long_lived_environments                    = toset(["default", "production", "preproduction", "development", "demo", "ur"])
  shared_mock_onelogin_load_balancer_enabled = !contains(local.long_lived_environments, local.environment_name)
  dns_namespace_env                          = local.environment.account_name == "production" ? "" : "${local.environment_name}."
  capacity_provider                          = local.environment.fargate_spot ? "FARGATE_SPOT" : "FARGATE"
  region                                     = data.aws_region.current.region

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = local.environment_name
    owner            = "Sarah Mills: sarah.mills@digital.justice.gov.uk"
    is-production    = local.environment.is_production
    service-area     = "POAS"
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk",
    account-name           = local.environment.account_name
  }

  mock_onelogin_version = "latest"

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
