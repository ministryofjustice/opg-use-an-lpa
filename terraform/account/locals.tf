locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account      = var.accounts[local.account_name]
  environment  = lower(terraform.workspace)


  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = local.environment
    owner            = "Sarah Mills: sarah.mills@digital.justice.gov.uk"
    is-production    = local.account.is_production
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
