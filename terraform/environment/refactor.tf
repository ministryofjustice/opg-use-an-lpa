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
