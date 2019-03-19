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
}
