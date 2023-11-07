locals {
  block_response = var.enable_block ? "NODATA" : null
  block_action   = var.enable_block ? "BLOCK" : "ALERT"
}

data "aws_region" "current" {
  provider = aws.region
}

data "aws_vpc" "default" {
  default = true

  provider = aws.region
}

resource "aws_cloudwatch_log_group" "aws_route53_resolver_query_log" {
  name              = "use-an-lpa-aws-route53-resolver-query-log-config"
  retention_in_days = 400
  kms_key_id        = var.kms_key_arn
  tags = {
    "Name" = "use-an-lpa-aws-route53-resolver-query-log-config"
  }

  provider = aws.region
}

resource "aws_route53_resolver_query_log_config" "egress" {
  name            = "egress"
  destination_arn = aws_cloudwatch_log_group.aws_route53_resolver_query_log.arn

  provider = aws.region
}

resource "aws_route53_resolver_query_log_config_association" "egress" {
  resolver_query_log_config_id = aws_route53_resolver_query_log_config.egress.id
  resource_id                  = data.aws_vpc.default.id

  provider = aws.region
}


locals {
  service_id = [
    "logs",
    "ecr",
    "dynamodb",
    "kms",
    "secretsmanager",
    "ecr.api",
  ]
}

data "aws_service" "services" {
  for_each   = toset(local.service_id)
  region     = data.aws_region.current.name
  service_id = each.value

  provider = aws.region
}

locals {
  aws_service_dns_name = [for service in data.aws_service.services : "${service.dns_name}."]
  interpolated_dns = [
    "${replace(var.brute_force_cache_primary_endpoint_address, "master", "*")}.",
    "prod-${data.aws_region.current.name}-starport-layer-bucket.s3.${data.aws_region.current.name}.amazonaws.com.",
    "public-keys.auth.elb.${data.aws_region.current.name}.amazonaws.com.",
    "311462405659.dkr.ecr.${data.aws_region.current.name}.amazonaws.com.",
    "*.ual.internal.ecs.",
  ]
}

resource "aws_route53_resolver_firewall_domain_list" "egress_allow" {
  name = "egress_allowed"
  domains = concat(
    local.interpolated_dns,
    local.aws_service_dns_name,
    var.domains_allowed
  )

  provider = aws.region
}

resource "aws_route53_resolver_firewall_domain_list" "egress_block" {
  name    = "egress_blocked"
  domains = var.domains_blocked

  provider = aws.region
}

resource "aws_route53_resolver_firewall_rule_group" "egress" {
  name = "egress"

  provider = aws.region
}

resource "aws_route53_resolver_firewall_rule" "egress_allow" {
  name                    = "egress_allowed"
  action                  = "ALLOW"
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_allow.id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress.id
  priority                = 200

  provider = aws.region
}

resource "aws_route53_resolver_firewall_rule" "egress_block" {
  name                    = "egress_blocked"
  action                  = local.block_action
  block_response          = local.block_response
  firewall_domain_list_id = aws_route53_resolver_firewall_domain_list.egress_block.id
  firewall_rule_group_id  = aws_route53_resolver_firewall_rule_group.egress.id
  priority                = 300

  provider = aws.region
}

resource "aws_route53_resolver_firewall_rule_group_association" "egress" {
  name                   = "egress"
  firewall_rule_group_id = aws_route53_resolver_firewall_rule_group.egress.id
  priority               = 500
  vpc_id                 = data.aws_vpc.default.id

  provider = aws.region
}


resource "aws_cloudwatch_query_definition" "dns_firewall_statistics" {
  name = "DNS Firewall Queries/DNS Firewall Statistics"

  log_group_names = [aws_cloudwatch_log_group.aws_route53_resolver_query_log.name]

  query_string = <<EOF
fields @timestamp, query_name, firewall_rule_action
| sort @timestamp desc
| stats count() as frequency by query_name, firewall_rule_action
EOF

  provider = aws.region
}
