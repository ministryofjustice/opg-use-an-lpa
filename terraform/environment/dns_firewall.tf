
resource "aws_route53_resolver_firewall_domain_list" "environment_egress_allow" {
  name = "${local.environment_name}_environment_egress_allow"
  domains = [
    local.api_service_fqdn,
    local.pdf_service_fqdn
  ]
}

resource "aws_route53_resolver_firewall_rule_group" "environment_egress_allow" {
  name = "${local.environment_name}_environment_egress_allow"
}

resource "aws_route53_resolver_firewall_rule" "environment_egress_allow" {
  name                    = "${local.environment_name}_environment_egress_allow"
  action                  = "ALLOW"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.environment_egress_allow.id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.environment_egress_allow.id
  priority                = 300
}

# how do I resolve the need to prioritise rules group association
resource "aws_route53_resolver_firewall_rule_group_association" "environment_egress_allow" {
  name                   = "${local.environment_name}_environment_egress_allow"
  firewall_rule_group_id = aws_route53_resolver_firewall_rule_group.environment_egress_allow.id
  priority               = 200
  vpc_id                 = data.aws_vpc.default.id
}
