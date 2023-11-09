moved {
  from = aws_cloudwatch_log_group.aws_route53_resolver_query_log[0]
  to   = module.eu_west_1.module.dns_firewall.aws_cloudwatch_log_group.aws_route53_resolver_query_log
}

moved {
  from = aws_cloudwatch_query_definition.dns_firewall_statistics[0]
  to   = module.eu_west_1.module.dns_firewall.aws_cloudwatch_query_definition.dns_firewall_statistics
}

moved {
  from = aws_route53_resolver_firewall_domain_list.egress_allow[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_firewall_domain_list.egress_allow
}

moved {
  from = aws_route53_resolver_firewall_domain_list.egress_block[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_firewall_domain_list.egress_block
}

moved {
  from = aws_route53_resolver_firewall_rule.egress_allow[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_firewall_rule.egress_allow
}

moved {
  from = aws_route53_resolver_firewall_rule.egress_block[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_firewall_rule.egress_block
}

moved {
  from = aws_route53_resolver_firewall_rule_group.egress[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_firewall_rule_group.egress
}

moved {
  from = aws_route53_resolver_firewall_rule_group_association.egress[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_firewall_rule_group_association.egress
}

moved {
  from = aws_route53_resolver_query_log_config.egress[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_query_log_config.egress
}

moved {
  from = aws_route53_resolver_query_log_config_association.egress[0]
  to   = module.eu_west_1.module.dns_firewall.aws_route53_resolver_query_log_config_association.egress
}

moved {
  from = aws_kms_alias.redacted_s3
  to   = module.eu_west_1.aws_kms_alias.redacted_s3
}

moved {
  from = aws_kms_key.redacted_s3
  to   = module.eu_west_1.aws_kms_key.redacted_s3
}

moved {
  from = aws_s3_bucket.access_log
  to   = module.eu_west_1.aws_s3_bucket.access_log
}

moved {
  from = aws_s3_bucket_acl.access_log
  to   = module.eu_west_1.aws_s3_bucket_acl.access_log
}

moved {
  from = aws_s3_bucket_logging.access_log
  to   = module.eu_west_1.aws_s3_bucket_logging.access_log
}

moved {
  from = aws_s3_bucket_policy.access_log
  to   = module.eu_west_1.aws_s3_bucket_policy.access_log
}

moved {
  from = aws_s3_bucket_public_access_block.access_log
  to   = module.eu_west_1.aws_s3_bucket_public_access_block.access_log
}

moved {
  from = aws_s3_bucket_server_side_encryption_configuration.access_log
  to   = module.eu_west_1.aws_s3_bucket_server_side_encryption_configuration.access_log
}

moved {
  from = aws_s3_bucket_versioning.access_log
  to   = module.eu_west_1.aws_s3_bucket_versioning.access_log
}

moved {
  from = aws_security_group.vpc_endpoints_private
  to   = module.eu_west_1.aws_security_group.vpc_endpoints_private
}

moved {
  from = aws_security_group_rule.vpc_endpoints_private_subnet_ingress
  to   = module.eu_west_1.aws_security_group_rule.vpc_endpoints_private_subnet_ingress
}

moved {
  from = aws_security_group_rule.vpc_endpoints_public_subnet_ingress
  to   = module.eu_west_1.aws_security_group_rule.vpc_endpoints_public_subnet_ingress
}

moved {
  from = aws_vpc_endpoint.private["ec2"]
  to   = module.eu_west_1.aws_vpc_endpoint.private["ec2"]
}

moved {
  from = module.redacted-logs.aws_s3_bucket.bucket
  to   = module.eu_west_1.module.redacted-logs.aws_s3_bucket.bucket
}

moved {
  from = module.redacted-logs.aws_s3_bucket_acl.bucket_acl
  to   = module.eu_west_1.module.redacted-logs.aws_s3_bucket_acl.bucket_acl
}

moved {
  from = module.redacted-logs.aws_s3_bucket_logging.bucket
  to   = module.eu_west_1.module.redacted-logs.aws_s3_bucket_logging.bucket
}

moved {
  from = module.redacted-logs.aws_s3_bucket_policy.bucket
  to   = module.eu_west_1.module.redacted-logs.aws_s3_bucket_policy.bucket
}

moved {
  from = module.redacted-logs.aws_s3_bucket_public_access_block.public_access_policy
  to   = module.eu_west_1.module.redacted-logs.aws_s3_bucket_public_access_block.public_access_policy
}

moved {
  from = module.redacted-logs.aws_s3_bucket_server_side_encryption_configuration.bucket_encryption_configuration
  to   = module.eu_west_1.module.redacted-logs.aws_s3_bucket_server_side_encryption_configuration.bucket_encryption_configuration
}

moved {
  from = module.redacted-logs.aws_s3_bucket_versioning.bucket_versioning
  to   = module.eu_west_1.module.redacted-logs.aws_s3_bucket_versioning.bucket_versioning
}

moved {
  from = aws_cloudwatch_log_group.use-an-lpa
  to   = module.eu_west_1.aws_cloudwatch_log_group.use-an-lpa
}

moved {
  from = aws_cloudwatch_log_group.vpc_flow_logs
  to   = module.eu_west_1.aws_cloudwatch_log_group.vpc_flow_logs
}

moved {
  from = aws_cloudwatch_log_group.waf_web_acl
  to   = module.eu_west_1.aws_cloudwatch_log_group.waf_web_acl
}

moved {
  from = aws_cloudwatch_metric_alarm.elasticache_high_cpu_utilization["brute-force-cache-replication-group-001"]
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.elasticache_high_cpu_utilization["brute-force-cache-replication-group-001"]
}

moved {
  from = aws_cloudwatch_metric_alarm.elasticache_high_cpu_utilization["brute-force-cache-replication-group-002"]
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.elasticache_high_cpu_utilization["brute-force-cache-replication-group-002"]
}

moved {
  from = aws_cloudwatch_metric_alarm.elasticache_high_swap_utilization["brute-force-cache-replication-group-001"]
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.elasticache_high_swap_utilization["brute-force-cache-replication-group-001"]
}

moved {
  from = aws_cloudwatch_metric_alarm.elasticache_high_swap_utilization["brute-force-cache-replication-group-002"]
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.elasticache_high_swap_utilization["brute-force-cache-replication-group-002"]
}

moved {
  from = aws_default_route_table.default
  to   = module.eu_west_1.aws_default_route_table.default
}

moved {
  from = aws_default_security_group.default
  to   = module.eu_west_1.aws_default_security_group.default
}

moved {
  from = aws_default_subnet.public[0]
  to   = module.eu_west_1.aws_default_subnet.public[0]
}

moved {
  from = aws_default_subnet.public[1]
  to   = module.eu_west_1.aws_default_subnet.public[1]
}

moved {
  from = aws_default_subnet.public[2]
  to   = module.eu_west_1.aws_default_subnet.public[2]
}

moved {
  from = aws_default_vpc.default
  to   = module.eu_west_1.aws_default_vpc.default
}

moved {
  from = aws_eip.nat[0]
  to   = module.eu_west_1.aws_eip.nat[0]
}

moved {
  from = aws_eip.nat[1]
  to   = module.eu_west_1.aws_eip.nat[1]
}

moved {
  from = aws_eip.nat[2]
  to   = module.eu_west_1.aws_eip.nat[2]
}

moved {
  from = aws_elasticache_replication_group.brute_force_cache_replication_group
  to   = module.eu_west_1.aws_elasticache_replication_group.brute_force_cache_replication_group
}

moved {
  from = aws_elasticache_subnet_group.private_subnets
  to   = module.eu_west_1.aws_elasticache_subnet_group.private_subnets
}

moved {
  from = aws_flow_log.vpc_flow_logs
  to   = module.eu_west_1.aws_flow_log.vpc_flow_logs
}

moved {
  from = module.eu_west_1.aws_iam_role.vpc_flow_logs
  to   = aws_iam_role.vpc_flow_logs
}

moved {
  from = module.eu_west_1.aws_iam_role_policy.vpc_flow_logs
  to   = aws_iam_role_policy.vpc_flow_logs
}

moved {
  from = aws_kms_alias.cloudwatch_alias
  to   = module.eu_west_1.aws_kms_alias.cloudwatch_alias
}

moved {
  from = aws_kms_alias.pagerduty_sns
  to   = module.eu_west_1.aws_kms_alias.pagerduty_sns
}

moved {
  from = aws_kms_alias.waf_cloudwatch_log_encryption
  to   = module.eu_west_1.aws_kms_alias.waf_cloudwatch_log_encryption
}

moved {
  from = aws_kms_key.cloudwatch
  to   = module.eu_west_1.aws_kms_key.cloudwatch
}

moved {
  from = aws_kms_key.pagerduty_sns
  to   = module.eu_west_1.aws_kms_key.pagerduty_sns
}

moved {
  from = aws_kms_key.waf_cloudwatch_log_encryption
  to   = module.eu_west_1.aws_kms_key.waf_cloudwatch_log_encryption
}

moved {
  from = aws_nat_gateway.nat[0]
  to   = module.eu_west_1.aws_nat_gateway.nat[0]
}

moved {
  from = aws_nat_gateway.nat[1]
  to   = module.eu_west_1.aws_nat_gateway.nat[1]
}

moved {
  from = aws_nat_gateway.nat[2]
  to   = module.eu_west_1.aws_nat_gateway.nat[2]
}

moved {
  from = aws_route.default
  to   = module.eu_west_1.aws_route.default
}

moved {
  from = aws_route.private[0]
  to   = module.eu_west_1.aws_route.private[0]
}

moved {
  from = aws_route.private[1]
  to   = module.eu_west_1.aws_route.private[1]
}

moved {
  from = aws_route.private[2]
  to   = module.eu_west_1.aws_route.private[2]
}

moved {
  from = aws_route_table.private[0]
  to   = module.eu_west_1.aws_route_table.private[0]
}

moved {
  from = aws_route_table.private[1]
  to   = module.eu_west_1.aws_route_table.private[1]
}

moved {
  from = aws_route_table.private[2]
  to   = module.eu_west_1.aws_route_table.private[2]
}

moved {
  from = aws_route_table_association.private[0]
  to   = module.eu_west_1.aws_route_table_association.private[0]
}

moved {
  from = aws_route_table_association.private[1]
  to   = module.eu_west_1.aws_route_table_association.private[1]
}

moved {
  from = aws_route_table_association.private[2]
  to   = module.eu_west_1.aws_route_table_association.private[2]
}

moved {
  from = aws_security_group.brute_force_cache_service
  to   = module.eu_west_1.aws_security_group.brute_force_cache_service
}

moved {
  from = aws_sns_topic.cloudwatch_to_pagerduty
  to   = module.eu_west_1.aws_sns_topic.cloudwatch_to_pagerduty
}

moved {
  from = aws_sns_topic_subscription.cloudwatch_sns_subscription
  to   = module.eu_west_1.aws_sns_topic_subscription.cloudwatch_sns_subscription
}

moved {
  from = aws_subnet.private[0]
  to   = module.eu_west_1.aws_subnet.private[0]
}

moved {
  from = aws_subnet.private[1]
  to   = module.eu_west_1.aws_subnet.private[1]
}

moved {
  from = aws_subnet.private[2]
  to   = module.eu_west_1.aws_subnet.private[2]
}

moved {
  from = aws_wafv2_web_acl.main
  to   = module.eu_west_1.aws_wafv2_web_acl.main
}

moved {
  from = aws_wafv2_web_acl_logging_configuration.main
  to   = module.eu_west_1.aws_wafv2_web_acl_logging_configuration.main
}

moved {
  from = pagerduty_service_integration.cloudwatch_integration
  to   = module.eu_west_1.pagerduty_service_integration.cloudwatch_integration
}

moved {
  from = aws_cloudwatch_log_group.workspace_cleanup_log
  to   = module.workspace_cleanup_mrk.aws_cloudwatch_log_group.workspace_cleanup_log
}

moved {
  from = aws_acm_certificate.certificate_admin
  to   = module.eu_west_1.aws_acm_certificate.certificate_admin
}

moved {
  from = aws_acm_certificate.certificate_public_facing_use
  to   = module.eu_west_1.aws_acm_certificate.certificate_public_facing_use
}

moved {
  from = aws_acm_certificate.certificate_public_facing_view
  to   = module.eu_west_1.aws_acm_certificate.certificate_public_facing_view
}

moved {
  from = aws_acm_certificate.certificate_use
  to   = module.eu_west_1.aws_acm_certificate.certificate_use
}

moved {
  from = aws_acm_certificate.certificate_view
  to   = module.eu_west_1.aws_acm_certificate.certificate_view
}

moved {
  from = aws_acm_certificate_validation.certificate_public_facing_use
  to   = module.eu_west_1.aws_acm_certificate_validation.certificate_public_facing_use
}

moved {
  from = aws_acm_certificate_validation.certificate_public_facing_view
  to   = module.eu_west_1.aws_acm_certificate_validation.certificate_public_facing_view
}

moved {
  from = aws_acm_certificate_validation.certificate_validation_admin
  to   = module.eu_west_1.aws_acm_certificate_validation.certificate_validation_admin
}

moved {
  from = aws_acm_certificate_validation.certificate_validation_use
  to   = module.eu_west_1.aws_acm_certificate_validation.certificate_validation_use
}

moved {
  from = aws_acm_certificate_validation.certificate_view
  to   = module.eu_west_1.aws_acm_certificate_validation.certificate_view
}

moved {
  from = aws_route53_record.certificate_validation_admin["*.admin.lastingpowerofattorney.opg.service.justice.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_admin["*.admin.lastingpowerofattorney.opg.service.justice.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_public_facing_use["*.use-lasting-power-of-attorney.service.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_public_facing_use["*.use-lasting-power-of-attorney.service.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_public_facing_view["*.view-lasting-power-of-attorney.service.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_public_facing_view["*.view-lasting-power-of-attorney.service.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_use["*.use.lastingpowerofattorney.opg.service.justice.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_use["*.use.lastingpowerofattorney.opg.service.justice.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_view["*.view.lastingpowerofattorney.opg.service.justice.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_view["*.view.lastingpowerofattorney.opg.service.justice.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_admin["admin.lastingpowerofattorney.opg.service.justice.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_admin["admin.lastingpowerofattorney.opg.service.justice.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_public_facing_use["use-lasting-power-of-attorney.service.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_public_facing_use["use-lasting-power-of-attorney.service.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_public_facing_view["view-lasting-power-of-attorney.service.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_public_facing_view["view-lasting-power-of-attorney.service.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_use["use.lastingpowerofattorney.opg.service.justice.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_use["use.lastingpowerofattorney.opg.service.justice.gov.uk"]
}

moved {
  from = aws_route53_record.certificate_validation_view["view.lastingpowerofattorney.opg.service.justice.gov.uk"]
  to   = module.eu_west_1.aws_route53_record.certificate_validation_view["view.lastingpowerofattorney.opg.service.justice.gov.uk"]
}
