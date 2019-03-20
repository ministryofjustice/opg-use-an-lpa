locals {
  target_accounts = {
    "development"   = "367815980639"
    "preproduction" = "888228022356"
    "production"    = "690083044361"
  }

  target_account = "${lookup(local.target_accounts, terraform.workspace)}"

  dns_prefixes = {
    "development"   = "dev.use-an-lpa"
    "preproduction" = "preprod.use-an-lpa"
    "production"    = "use-an-lpa"
  }

  dns_prefix = "${lookup(local.dns_prefixes, terraform.workspace)}"

  is_production = {
    "development"   = "false"
    "preproduction" = "false"
    "production"    = "true"
  }

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "use-an-lpa"
    environment-name = "${terraform.workspace}"
    owner            = "Katie Gibbs: katie.gibbs@digital.justice.gov.uk"
    is-production    = "${lookup(local.is_production, terraform.workspace)}"
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opgteam+use-an-lpa-prod@digital.justice.gov.uk"
  }

  default_tags = "${merge(local.mandatory_moj_tags,local.optional_tags)}"
}
