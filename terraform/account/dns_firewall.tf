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

resource "aws_route53_resolver_firewall_domain_list" "egress_allow" {
  count   = local.account.dns_firewall.enabled ? 1 : 0
  name    = "egress"
  domains = local.account.dns_firewall.domains_allowed
}

resource "aws_route53_resolver_firewall_domain_list" "egress_block" {
  count   = local.account.dns_firewall.enabled ? 1 : 0
  name    = "egress"
  domains = ["*"]
}

resource "aws_route53_resolver_firewall_rule_group" "egress" {
  count = local.account.dns_firewall.enabled ? 1 : 0
  name  = "egress"
}

resource "aws_route53_resolver_firewall_rule" "egress_allow" {
  count                   = local.account.dns_firewall.enabled ? 1 : 0
  name                    = "egress"
  action                  = "ALLOW"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_allow[0].id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority                = 100
}

resource "aws_route53_resolver_firewall_rule" "egress_block" {
  count                   = local.account.dns_firewall.enabled ? 1 : 0
  name                    = "egress"
  action                  = "BLOCK"
  block_response          = "NODATA"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_block[0].id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority                = 200
}

resource "aws_route53_resolver_firewall_rule_group_association" "egress" {
  count                  = local.account.dns_firewall.enabled ? 1 : 0
  name                   = "egress"
  firewall_rule_group_id = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority               = 200
  vpc_id                 = aws_default_vpc.default.id
}
