moved {
  from = aws_ecs_cluster.use-an-lpa
  to   = module.eu_west_1.aws_ecs_cluster.use_an_lpa
}

moved {
  from = aws_ecs_service.actor
  to   = module.eu_west_1.aws_ecs_service.actor
}

moved {
  from = aws_ecs_service.admin
  to   = module.eu_west_1.aws_ecs_service.admin
}

moved {
  from = aws_ecs_service.api
  to   = module.eu_west_1.aws_ecs_service.api
}

moved {
  from = aws_ecs_service.pdf
  to   = module.eu_west_1.aws_ecs_service.pdf
}

moved {
  from = aws_ecs_service.viewer
  to   = module.eu_west_1.aws_ecs_service.viewer
}

moved {
  from = aws_ecs_task_definition.actor
  to   = module.eu_west_1.aws_ecs_task_definition.actor
}

moved {
  from = aws_ecs_task_definition.admin
  to   = module.eu_west_1.aws_ecs_task_definition.admin
}

moved {
  from = aws_ecs_task_definition.api
  to   = module.eu_west_1.aws_ecs_task_definition.api
}

moved {
  from = aws_ecs_task_definition.pdf
  to   = module.eu_west_1.aws_ecs_task_definition.pdf
}

moved {
  from = aws_ecs_task_definition.viewer
  to   = module.eu_west_1.aws_ecs_task_definition.viewer
}

moved {
  from = aws_iam_role_policy.actor_permissions_role
  to   = module.eu_west_1.aws_iam_role_policy.actor_permissions_role
}

moved {
  from = aws_iam_role_policy.admin_permissions_role
  to   = module.eu_west_1.aws_iam_role_policy.admin_permissions_role
}

moved {
  from = aws_iam_role_policy.api_permissions_role
  to   = module.eu_west_1.aws_iam_role_policy.api_permissions_role
}

moved {
  from = aws_iam_role_policy.execution_role
  to   = module.eu_west_1.aws_iam_role_policy.execution_role
}

moved {
  from = aws_iam_role_policy.viewer_permissions_role
  to   = module.eu_west_1.aws_iam_role_policy.viewer_permissions_role
}

moved {
  from = aws_security_group.actor_ecs_service
  to   = module.eu_west_1.aws_security_group.actor_ecs_service
}

moved {
  from = aws_security_group.admin_ecs_service
  to   = module.eu_west_1.aws_security_group.admin_ecs_service
}

moved {
  from = aws_security_group.api_ecs_service
  to   = module.eu_west_1.aws_security_group.api_ecs_service
}

moved {
  from = aws_security_group.pdf_ecs_service
  to   = module.eu_west_1.aws_security_group.pdf_ecs_service
}

moved {
  from = aws_security_group.viewer_ecs_service
  to   = module.eu_west_1.aws_security_group.viewer_ecs_service
}

moved {
  from = aws_security_group_rule.actor_ecs_service_egress
  to   = module.eu_west_1.aws_security_group_rule.actor_ecs_service_egress
}

moved {
  from = aws_security_group_rule.actor_ecs_service_elasticache_ingress
  to   = module.eu_west_1.aws_security_group_rule.actor_ecs_service_elasticache_ingress
}

moved {
  from = aws_security_group_rule.actor_ecs_service_ingress
  to   = module.eu_west_1.aws_security_group_rule.actor_ecs_service_ingress
}

moved {
  from = aws_security_group_rule.admin_ecs_service_egress
  to   = module.eu_west_1.aws_security_group_rule.admin_ecs_service_egress
}

moved {
  from = aws_security_group_rule.admin_ecs_service_ingress
  to   = module.eu_west_1.aws_security_group_rule.admin_ecs_service_ingress
}

moved {
  from = aws_security_group_rule.api_ecs_service_actor_ingress
  to   = module.eu_west_1.aws_security_group_rule.api_ecs_service_actor_ingress
}

moved {
  from = aws_security_group_rule.api_ecs_service_egress
  to   = module.eu_west_1.aws_security_group_rule.api_ecs_service_egress
}

moved {
  from = aws_security_group_rule.api_ecs_service_viewer_ingress
  to   = module.eu_west_1.aws_security_group_rule.api_ecs_service_viewer_ingress
}

moved {
  from = aws_security_group_rule.pdf_ecs_service_egress
  to   = module.eu_west_1.aws_security_group_rule.pdf_ecs_service_egress
}

moved {
  from = aws_security_group_rule.pdf_ecs_service_viewer_ingress
  to   = module.eu_west_1.aws_security_group_rule.pdf_ecs_service_viewer_ingress
}

moved {
  from = aws_security_group_rule.viewer_ecs_service_egress
  to   = module.eu_west_1.aws_security_group_rule.viewer_ecs_service_egress
}

moved {
  from = aws_security_group_rule.viewer_ecs_service_elasticache_ingress
  to   = module.eu_west_1.aws_security_group_rule.viewer_ecs_service_elasticache_ingress
}

moved {
  from = aws_security_group_rule.viewer_ecs_service_ingress
  to   = module.eu_west_1.aws_security_group_rule.viewer_ecs_service_ingress
}

moved {
  from = aws_service_discovery_service.api_ecs
  to   = module.eu_west_1.aws_service_discovery_service.api_ecs
}

moved {
  from = aws_service_discovery_service.pdf_ecs
  to   = module.eu_west_1.aws_service_discovery_service.pdf_ecs
}

moved {
  from = aws_lb.actor
  to   = module.eu_west_1.aws_lb.actor
}

moved {
  from = aws_lb.admin
  to   = module.eu_west_1.aws_lb.admin
}

moved {
  from = aws_lb.viewer
  to   = module.eu_west_1.aws_lb.viewer
}

moved {
  from = aws_lb_listener.actor_loadbalancer
  to   = module.eu_west_1.aws_lb_listener.actor_loadbalancer
}

moved {
  from = aws_lb_listener.actor_loadbalancer_http_redirect
  to   = module.eu_west_1.aws_lb_listener.actor_loadbalancer_http_redirect
}

moved {
  from = aws_lb_listener.admin_loadbalancer
  to   = module.eu_west_1.aws_lb_listener.admin_loadbalancer
}
moved {
  from = aws_lb_listener.admin_loadbalancer_http_redirect
  to   = module.eu_west_1.aws_lb_listener.admin_loadbalancer_http_redirect
}

moved {
  from = aws_lb_listener.viewer_loadbalancer
  to   = module.eu_west_1.aws_lb_listener.viewer_loadbalancer
}

moved {
  from = aws_lb_listener.viewer_loadbalancer_http_redirect
  to   = module.eu_west_1.aws_lb_listener.viewer_loadbalancer_http_redirect
}

moved {
  from = aws_lb_listener_certificate.actor_loadbalancer_live_service_certificate
  to   = module.eu_west_1.aws_lb_listener_certificate.actor_loadbalancer_live_service_certificate
}

moved {
  from = aws_lb_listener_certificate.admin_loadbalancer_live_service_certificate
  to   = module.eu_west_1.aws_lb_listener_certificate.admin_loadbalancer_live_service_certificate
}

moved {
  from = aws_lb_listener_certificate.viewer_loadbalancer_live_service_certificate
  to   = module.eu_west_1.aws_lb_listener_certificate.viewer_loadbalancer_live_service_certificate
}

moved {
  from = aws_lb_listener_rule.actor_maintenance
  to   = module.eu_west_1.aws_lb_listener_rule.actor_maintenance
}

moved {
  from = aws_lb_listener_rule.actor_maintenance_welsh
  to   = module.eu_west_1.aws_lb_listener_rule.actor_maintenance_welsh
}

moved {
  from = aws_lb_listener_rule.redirect_use_root_to_gov
  to   = module.eu_west_1.aws_lb_listener_rule.redirect_use_root_to_gov
}

moved {
  from = aws_lb_listener_rule.redirect_view_root_to_gov
  to   = module.eu_west_1.aws_lb_listener_rule.redirect_view_root_to_gov
}

moved {
  from = aws_lb_listener_rule.rewrite_use_to_live_service_url
  to   = module.eu_west_1.aws_lb_listener_rule.rewrite_use_to_live_service_url
}

moved {
  from = aws_lb_listener_rule.rewrite_view_to_live_service_url
  to   = module.eu_west_1.aws_lb_listener_rule.rewrite_view_to_live_service_url
}

moved {
  from = aws_lb_listener_rule.viewer_maintenance
  to   = module.eu_west_1.aws_lb_listener_rule.viewer_maintenance
}

moved {
  from = aws_lb_listener_rule.viewer_maintenance_welsh
  to   = module.eu_west_1.aws_lb_listener_rule.viewer_maintenance_welsh
}

moved {
  from = aws_lb_target_group.actor
  to   = module.eu_west_1.aws_lb_target_group.actor
}

moved {
  from = aws_lb_target_group.admin
  to   = module.eu_west_1.aws_lb_target_group.admin
}

moved {
  from = aws_lb_target_group.viewer
  to   = module.eu_west_1.aws_lb_target_group.viewer
}

moved {
  from = aws_security_group.actor_loadbalancer
  to   = module.eu_west_1.aws_security_group.actor_loadbalancer
}

moved {
  from = aws_security_group.actor_loadbalancer_route53
  to   = module.eu_west_1.aws_security_group.actor_loadbalancer_route53
}

moved {
  from = aws_security_group.admin_loadbalancer
  to   = module.eu_west_1.aws_security_group.admin_loadbalancer
}

moved {
  from = aws_security_group.viewer_loadbalancer
  to   = module.eu_west_1.aws_security_group.viewer_loadbalancer
}

moved {
  from = aws_security_group.viewer_loadbalancer_route53
  to   = module.eu_west_1.aws_security_group.viewer_loadbalancer_route53
}

moved {
  from = aws_security_group_rule.actor_loadbalancer_egress
  to   = module.eu_west_1.aws_security_group_rule.actor_loadbalancer_egress
}

moved {
  from = aws_security_group_rule.actor_loadbalancer_ingress
  to   = module.eu_west_1.aws_security_group_rule.actor_loadbalancer_ingress
}

moved {
  from = aws_security_group_rule.actor_loadbalancer_ingress_http
  to   = module.eu_west_1.aws_security_group_rule.actor_loadbalancer_ingress_http
}

moved {
  from = aws_security_group_rule.actor_loadbalancer_ingress_route53_healthchecks
  to   = module.eu_west_1.aws_security_group_rule.actor_loadbalancer_ingress_route53_healthchecks
}

moved {
  from = aws_security_group_rule.admin_loadbalancer_egress
  to   = module.eu_west_1.aws_security_group_rule.admin_loadbalancer_egress
}

moved {
  from = aws_security_group_rule.admin_loadbalancer_ingress
  to   = module.eu_west_1.aws_security_group_rule.admin_loadbalancer_ingress
}

moved {
  from = aws_security_group_rule.admin_loadbalancer_port_80_redirect_ingress
  to   = module.eu_west_1.aws_security_group_rule.admin_loadbalancer_port_80_redirect_ingress
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_egress
  to   = module.eu_west_1.aws_security_group_rule.viewer_loadbalancer_egress
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress
  to   = module.eu_west_1.aws_security_group_rule.viewer_loadbalancer_ingress
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress_http
  to   = module.eu_west_1.aws_security_group_rule.viewer_loadbalancer_ingress_http
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress_route53_healthchecks
  to   = module.eu_west_1.aws_security_group_rule.viewer_loadbalancer_ingress_route53_healthchecks
}

moved {
  from = aws_ssm_parameter.actor_maintenance_switch
  to   = module.eu_west_1.aws_ssm_parameter.actor_maintenance_switch
}

moved {
  from = aws_ssm_parameter.viewer_maintenance_switch
  to   = module.eu_west_1.aws_ssm_parameter.viewer_maintenance_switch
}

moved {
  from = aws_security_group_rule.actor_loadbalancer_ingress_production[0]
  to   = module.eu_west_1.aws_security_group_rule.actor_loadbalancer_ingress_public_access[0]
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress_public_access[0]
  to   = module.eu_west_1.aws_security_group_rule.viewer_loadbalancer_ingress_public_access[0]
}

moved {
  from = aws_cloudwatch_log_group.application_logs
  to   = module.eu_west_1.aws_cloudwatch_log_group.application_logs
}

moved {
  from = aws_cloudwatch_log_metric_filter.api_5xx_errors
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.api_5xx_errors
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACCOUNT_ACTIVATED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACCOUNT_ACTIVATED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACCOUNT_CREATED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACCOUNT_CREATED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACCOUNT_DELETED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACCOUNT_DELETED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACTIVATION_KEY_REQUEST_REPLACEMENT_ATTORNEY"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ACTIVATION_KEY_REQUEST_REPLACEMENT_ATTORNEY"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADDED_LPA_TYPE_HW"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADDED_LPA_TYPE_HW"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADDED_LPA_TYPE_PFA"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADDED_LPA_TYPE_PFA"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_ALREADY_ADDED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_ALREADY_ADDED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_FAILURE"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_FAILURE"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_FOUND"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_FOUND"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_NOT_ELIGIBLE"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_NOT_ELIGIBLE"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_NOT_FOUND"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_NOT_FOUND"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_SUCCESS"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.ADD_LPA_SUCCESS"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.DOWNLOAD_SUMMARY"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.DOWNLOAD_SUMMARY"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.IDENTITY_HASH_CHANGE"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.IDENTITY_HASH_CHANGE"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.LPA_REMOVED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.LPA_REMOVED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_ALREADY_ADDED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_ALREADY_ADDED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_DOES_NOT_MATCH"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_DOES_NOT_MATCH"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_FORCE_ACTIVATION_KEY"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_FORCE_ACTIVATION_KEY"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_FOUND"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_FOUND"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_HAS_ACTIVATION_KEY"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_HAS_ACTIVATION_KEY"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_INVALID_STATUS"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_INVALID_STATUS"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_KEY_ALREADY_REQUESTED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_KEY_ALREADY_REQUESTED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_NEEDS_CLEANSING"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_NEEDS_CLEANSING"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_NOT_ELIGIBLE"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_NOT_ELIGIBLE"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_NOT_FOUND"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_NOT_FOUND"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_PARTIAL_MATCH_TOO_RECENT"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_PARTIAL_MATCH_TOO_RECENT"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_SUCCESS"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_SUCCESS"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_TOO_OLD"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.OLDER_LPA_TOO_OLD"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.SHARE_CODE_NOT_FOUND"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.SHARE_CODE_NOT_FOUND"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.UNEXPECTED_DATA_LPA_API_RESPONSE"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.UNEXPECTED_DATA_LPA_API_RESPONSE"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.USER_ABROAD_ADDRESS_REQUEST_SUCCESS"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.USER_ABROAD_ADDRESS_REQUEST_SUCCESS"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_CANCELLED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_CANCELLED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_EXPIRED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_EXPIRED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_NOT_FOUND"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_NOT_FOUND"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_SUCCESS"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["event_code.VIEW_LPA_SHARE_CODE_SUCCESS"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["key_status.ACTIVATION_KEY_EXISTS"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["key_status.ACTIVATION_KEY_EXISTS"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["key_status.ACTIVATION_KEY_EXPIRED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["key_status.ACTIVATION_KEY_EXPIRED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["key_status.ACTIVATION_KEY_NOT_EXISTS"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["key_status.ACTIVATION_KEY_NOT_EXISTS"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["phone.OOLPA_PHONE_NUMBER_NOT_PROVIDED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["phone.OOLPA_PHONE_NUMBER_NOT_PROVIDED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["phone.OOLPA_PHONE_NUMBER_PROVIDED"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["phone.OOLPA_PHONE_NUMBER_PROVIDED"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["role.OOLPA_KEY_REQUESTED_FOR_ATTORNEY"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["role.OOLPA_KEY_REQUESTED_FOR_ATTORNEY"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.log_event_code_metrics["role.OOLPA_KEY_REQUESTED_FOR_DONOR"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.log_event_code_metrics["role.OOLPA_KEY_REQUESTED_FOR_DONOR"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.login_attempt_failures["401"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.login_attempt_failures["401"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.login_attempt_failures["403"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.login_attempt_failures["403"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.login_attempt_failures["404"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.login_attempt_failures["404"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.rate_limiting_metrics["actor_code_failure"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.rate_limiting_metrics["actor_code_failure"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.rate_limiting_metrics["actor_login_failure"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.rate_limiting_metrics["actor_login_failure"]
}

moved {
  from = aws_cloudwatch_log_metric_filter.rate_limiting_metrics["viewer_code_failure"]
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.rate_limiting_metrics["viewer_code_failure"]
}

moved {
  from = aws_cloudwatch_metric_alarm.actor_5xx_errors
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.actor_5xx_errors
}

moved {
  from = aws_cloudwatch_metric_alarm.actor_ddos_attack_external
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.actor_ddos_attack_external
}

moved {
  from = aws_cloudwatch_metric_alarm.admin_ddos_attack_external
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.admin_ddos_attack_external
}

moved {
  from = aws_cloudwatch_metric_alarm.api_5xx_errors
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.api_5xx_errors
}

moved {
  from = aws_cloudwatch_metric_alarm.unexpected_data_lpa_api_resposnes
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.unexpected_data_lpa_api_resposnes
}

moved {
  from = aws_cloudwatch_metric_alarm.viewer_5xx_errors
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.viewer_5xx_errors
}

moved {
  from = aws_cloudwatch_metric_alarm.viewer_ddos_attack_external
  to   = module.eu_west_1.aws_cloudwatch_metric_alarm.viewer_ddos_attack_external
}

moved {
  from = aws_cloudwatch_query_definition.app_container_messages
  to   = module.eu_west_1.aws_cloudwatch_query_definition.app_container_messages
}

moved {
  from = aws_cloudwatch_log_group.application_logs
  to   = module.eu_west_1.aws_cloudwatch_log_group.application_logs
}

moved {
  from = aws_cloudwatch_log_metric_filter.api_5xx_errors
  to   = module.eu_west_1.aws_cloudwatch_log_metric_filter.api_5xx_errors
}

moved {
  from = aws_sns_topic.cloudwatch_to_pagerduty
  to   = module.eu_west_1.aws_sns_topic.cloudwatch_to_pagerduty
}

moved {
  from = pagerduty_service_integration.cloudwatch_integration
  to   = module.eu_west_1.pagerduty_service_integration.cloudwatch_integration
}

moved {
  from = aws_sns_topic_subscription.cloudwatch_sns_subscription
  to   = module.eu_west_1.aws_sns_topic_subscription.cloudwatch_sns_subscription
}

moved {
  from = module.api_ecs_autoscaling.aws_appautoscaling_policy.down
  to   = module.eu_west_1.module.api_ecs_autoscaling.aws_appautoscaling_policy.down
}

moved {
  from = module.api_ecs_autoscaling.aws_appautoscaling_policy.up
  to   = module.eu_west_1.module.api_ecs_autoscaling.aws_appautoscaling_policy.up
}

moved {
  from = module.api_ecs_autoscaling.aws_appautoscaling_target.ecs_service
  to   = module.eu_west_1.module.api_ecs_autoscaling.aws_appautoscaling_target.ecs_service
}

moved {
  from = module.api_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
  to   = module.eu_west_1.module.api_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
}

moved {
  from = module.api_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
  to   = module.eu_west_1.module.api_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
}

moved {
  from = module.api_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
  to   = module.eu_west_1.module.api_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
}

moved {
  from = module.pdf_ecs_autoscaling.aws_appautoscaling_policy.down
  to   = module.eu_west_1.module.pdf_ecs_autoscaling.aws_appautoscaling_policy.down
}

moved {
  from = module.pdf_ecs_autoscaling.aws_appautoscaling_policy.up
  to   = module.eu_west_1.module.pdf_ecs_autoscaling.aws_appautoscaling_policy.up
}

moved {
  from = module.pdf_ecs_autoscaling.aws_appautoscaling_target.ecs_service
  to   = module.eu_west_1.module.pdf_ecs_autoscaling.aws_appautoscaling_target.ecs_service
}

moved {
  from = module.pdf_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
  to   = module.eu_west_1.module.pdf_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
}

moved {
  from = module.pdf_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
  to   = module.eu_west_1.module.pdf_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
}

moved {
  from = module.pdf_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
  to   = module.eu_west_1.module.pdf_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
}

moved {
  from = module.use_ecs_autoscaling.aws_appautoscaling_policy.down
  to   = module.eu_west_1.module.use_ecs_autoscaling.aws_appautoscaling_policy.down
}

moved {
  from = module.use_ecs_autoscaling.aws_appautoscaling_policy.up
  to   = module.eu_west_1.module.use_ecs_autoscaling.aws_appautoscaling_policy.up
}

moved {
  from = module.use_ecs_autoscaling.aws_appautoscaling_target.ecs_service
  to   = module.eu_west_1.module.use_ecs_autoscaling.aws_appautoscaling_target.ecs_service
}

moved {
  from = module.use_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
  to   = module.eu_west_1.module.use_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
}

moved {
  from = module.use_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
  to   = module.eu_west_1.module.use_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
}

moved {
  from = module.use_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
  to   = module.eu_west_1.module.use_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
}

moved {
  from = module.view_ecs_autoscaling.aws_appautoscaling_policy.down
  to   = module.eu_west_1.module.view_ecs_autoscaling.aws_appautoscaling_policy.down
}

moved {
  from = module.view_ecs_autoscaling.aws_appautoscaling_policy.up
  to   = module.eu_west_1.module.view_ecs_autoscaling.aws_appautoscaling_policy.up
}

moved {
  from = module.view_ecs_autoscaling.aws_appautoscaling_target.ecs_service
  to   = module.eu_west_1.module.view_ecs_autoscaling.aws_appautoscaling_target.ecs_service
}

moved {
  from = module.view_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
  to   = module.eu_west_1.module.view_ecs_autoscaling.aws_cloudwatch_metric_alarm.max_scaling_reached
}

moved {
  from = module.view_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
  to   = module.eu_west_1.module.view_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_down
}

moved {
  from = module.view_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
  to   = module.eu_west_1.module.view_ecs_autoscaling.aws_cloudwatch_metric_alarm.scale_up
}

moved {
  from = aws_cloudwatch_log_subscription_filter.events
  to   = module.eu_west_1.aws_cloudwatch_log_subscription_filter.events
}

moved {
  from = aws_lambda_permission.allow_cloudwatch
  to   = module.eu_west_1.aws_lambda_permission.allow_cloudwatch
}

moved {
  from = aws_cloudwatch_metric_alarm.actor_health_check_alarm
  to   = module.eu_west_1.module.actor_use_my_lpa.aws_cloudwatch_metric_alarm.this[0]
}

moved {
  from = aws_cloudwatch_metric_alarm.viewer_health_check_alarm
  to   = module.eu_west_1.module.viewer_use_my_lpa.aws_cloudwatch_metric_alarm.this[0]
}

moved {
  from = aws_route53_health_check.actor_health_check
  to   = module.eu_west_1.module.actor_use_my_lpa.aws_route53_health_check.this[0]
}

moved {
  from = aws_route53_health_check.viewer_health_check
  to   = module.eu_west_1.module.viewer_use_my_lpa.aws_route53_health_check.this[0]
}

moved {
  from = aws_route53_record.actor_use_my_lpa
  to   = module.eu_west_1.module.actor_use_my_lpa.aws_route53_record.this
}

moved {
  from = aws_route53_record.admin_use_my_lpa
  to   = module.eu_west_1.module.admin_use_my_lpa.aws_route53_record.this
}

moved {
  from = aws_route53_record.public_facing_use_lasting_power_of_attorney
  to   = module.eu_west_1.module.public_facing_use_lasting_power_of_attorney.aws_route53_record.this
}

moved {
  from = aws_route53_record.public_facing_view_lasting_power_of_attorney
  to   = module.eu_west_1.module.public_facing_view_lasting_power_of_attorney.aws_route53_record.this
}

moved {
  from = aws_route53_record.viewer_use_my_lpa
  to   = module.eu_west_1.module.viewer_use_my_lpa.aws_route53_record.this
}

moved {
  from = aws_service_discovery_private_dns_namespace.internal_ecs
  to   = module.eu_west_1.aws_service_discovery_private_dns_namespace.internal_ecs
}
