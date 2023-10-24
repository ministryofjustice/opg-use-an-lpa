module "dns_firewall" {
  source = "./modules/dns_firewall"

  kms_key_arn                                = aws_kms_key.cloudwatch.arn
  domains_allowed                            = var.account.dns_firewall.domains_allowed
  domains_blocked                            = var.account.dns_firewall.domains_blocked
  brute_force_cache_primary_endpoint_address = aws_elasticache_replication_group.brute_force_cache_replication_group.primary_endpoint_address

  providers = {
    aws.region = aws.region
  }
}
