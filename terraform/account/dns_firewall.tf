resource "aws_route53_resolver_firewall_domain_list" "egress_allow" {
  count   = local.account.dns_firewall.enabled == true ? 1 : 0
  name    = "egress"
  domains = local.account.dns_firewall.domains
}

# resource "aws_route53_resolver_firewall_domain_list" "egress_block" {
#   count   = local.account.dns_firewall.enabled == true ? 1 : 0
#   name    = "egress"
#   domains = local.account.dns_firewall.domains
# }

resource "aws_route53_resolver_firewall_rule_group" "egress" {
  count = local.account.dns_firewall.enabled == true ? 1 : 0
  name  = "egress"
}

resource "aws_route53_resolver_firewall_rule" "egress_allow" {
  count                   = local.account.dns_firewall.enabled == true ? 1 : 0
  name                    = "egress"
  action                  = "ALLOW"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_allow[0].id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority                = 100
}

# resource "aws_route53_resolver_firewall_rule" "egress_block" {
#   count                   = local.account.dns_firewall.enabled == true ? 1 : 0
#   name                    = "egress"
#   action                  = "BLOCK"
#   block_response          = "NODATA"
#   firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_block[0].id
#   firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress[0].id
#   priority                = 100
# }

resource "aws_route53_resolver_firewall_rule_group_association" "egress" {
  count                  = local.account.dns_firewall.enabled == true ? 1 : 0
  name                   = "egress"
  firewall_rule_group_id = aws_route53_resolver_firewall_rule_group.egress[0].id
  priority               = 100
  vpc_id                 = aws_default_vpc.default.id
}
