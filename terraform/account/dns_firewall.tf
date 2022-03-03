resource "aws_cloudwatch_log_group" "aws_route53_resolver_query_log" {
  count             = local.account.dns_firewall.enabled ? 1 : 0
  name              = "use-an-lpa-aws-route53-resolver-query-log-config"
  retention_in_days = 400
  kms_key_id        = aws_kms_key.cloudwatch.arn
  tags = {
    "Name" = "use-an-lpa-aws-route53-resolver-query-log-config"
  }
}

resource "aws_route53_resolver_query_log_config" "egress" {
  count           = local.account.dns_firewall.enabled ? 1 : 0
  name            = "egress"
  destination_arn = aws_cloudwatch_log_group.aws_route53_resolver_query_log[0].arn
}

resource "aws_route53_resolver_query_log_config_association" "egress" {
  count                        = local.account.dns_firewall.enabled ? 1 : 0
  resolver_query_log_config_id = aws_route53_resolver_query_log_config.egress[0].id
  resource_id                  = aws_default_vpc.default.id
}

locals {
  interpolated_dns = [
    "logs.${data.aws_region.current.name}.amazonaws.com.",
    "logs.${data.aws_region.current.name}.amazonaws.com.${data.aws_region.current.name}.compute.internal.",
    "api.ecr.${data.aws_region.current.name}.amazonaws.com.",
    "api.ecr.${data.aws_region.current.name}.amazonaws.com.${data.aws_region.current.name}.compute.internal.",
    "dynamodb.${data.aws_region.current.name}.amazonaws.com.",
    "kms.${data.aws_region.current.name}.amazonaws.com.",
    "secretsmanager.${data.aws_region.current.name}.amazonaws.com.",
    "secretsmanager.${data.aws_region.current.name}.amazonaws.com.${data.aws_region.current.name}.compute.internal.",
    "${replace(aws_elasticache_replication_group.brute_force_cache_replication_group.primary_endpoint_address, "master", "*")}.",
    "311462405659.dkr.ecr.eu-west-1.amazonaws.com.",
    "api.${local.environment}-internal.",
    "pdf.${local.environment}-internal.",
  ]
}
resource "aws_route53_resolver_firewall_domain_list" "egress_allow" {
  count   = local.account.dns_firewall.enabled ? 1 : 0
  name    = "egress_allowed"
  domains = concat(local.interpolated_dns, local.account.dns_firewall.domains_allowed)
}

resource "aws_route53_resolver_firewall_domain_list" "egress_block" {
  count   = local.account.dns_firewall.enabled ? 1 : 0
  name    = "egress_blocked"
  domains = local.account.dns_firewall.domains_blocked
}

resource "aws_route53_resolver_firewall_rule_group" "egress" {
  count = local.account.dns_firewall.enabled ? 1 : 0
  name  = "egress"
}

resource "aws_route53_resolver_firewall_rule" "egress_allow" {
  count                   = local.account.dns_firewall.enabled ? 1 : 0
  name                    = "egress_allowed"
  action                  = "ALLOW"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_allow[0].id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority                = 200
}

resource "aws_route53_resolver_firewall_rule" "egress_block" {
  count  = local.account.dns_firewall.enabled ? 1 : 0
  name   = "egress_blocked"
  action = "ALERT"
  # action                  = "BLOCK"
  # block_response          = "NODATA"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_block[0].id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority                = 300
}

resource "aws_route53_resolver_firewall_rule_group_association" "egress" {
  count                  = local.account.dns_firewall.enabled ? 1 : 0
  name                   = "egress"
  firewall_rule_group_id = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority               = 300
  vpc_id                 = aws_default_vpc.default.id
}


resource "aws_cloudwatch_query_definition" "dns_firewall_statistics" {
  count = local.account.dns_firewall.enabled ? 1 : 0
  name  = "DNS Firewall Queries/DNS Firewall Statistics"

  log_group_names = [aws_cloudwatch_log_group.aws_route53_resolver_query_log[0].name]

  query_string = <<EOF
fields @timestamp, query_name, firewall_rule_action
| sort @timestamp desc
| stats count() as frequency by query_name, firewall_rule_action
EOF
}
