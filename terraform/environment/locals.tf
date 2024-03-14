locals {
  environment_name  = lower(replace(terraform.workspace, "_", "-"))
  environment       = contains(keys(var.environments), local.environment_name) ? var.environments[local.environment_name] : var.environments["default"]
  dns_namespace_env = local.environment.account_name == "production" ? "" : "${local.environment_name}."
  capacity_provider = local.environment.fargate_spot ? "FARGATE_SPOT" : "FARGATE"

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = local.environment_name
    owner            = "Sarah Mills: sarah.mills@digital.justice.gov.uk"
    is-production    = local.environment.is_production
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
