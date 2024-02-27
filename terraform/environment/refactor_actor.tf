# These resources were renamed from 'actor' to 'use' as part of ticket UML-972


moved {
  from = module.eu_west_1[0].aws_lb.actor
  to   = module.eu_west_1[0].aws_lb.use
}

moved {
  from = module.eu_west_1[0].aws_lb_listener.actor_loadbalancer
  to   = module.eu_west_1[0].aws_lb_listener.use_loadbalancer
}

moved {
  from = module.eu_west_1[0].aws_lb_listener_certificate.actor_live_service_certificate
  to   = module.eu_west_1[0].aws_lb_listener_certificate.use_live_service_certificate
}

moved {
  from = module.eu_west_1[0].aws_lb_listener_rule.actor_maintenance
  to   = module.eu_west_1[0].aws_lb_listener_rule.use_maintenance
}

moved {
  from = module.eu_west_1[0].aws_lb_listener_rule.actor_maintenance_welsh
  to   = module.eu_west_1[0].aws_lb_listener_rule.use_maintenance_welsh
}

moved {
  from = module.eu_west_1[0].aws_lb_target_group.actor
  to   = module.eu_west_1[0].aws_lb_target_group.use
}

moved {
  from = module.eu_west_1[0].aws_security_group.actor_loadbalancer
  to   = module.eu_west_1[0].aws_security_group.use_loadbalancer
}

moved {
  from = module.eu_west_1[0].aws_security_group.actor_loadbalancer_route53
  to   = module.eu_west_1[0].aws_security_group.use_loadbalancer_route53
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_ingress
  to   = module.eu_west_1[0].aws_security_group_rule.use_ingress
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_ingress_http
  to   = module.eu_west_1[0].aws_security_group_rule.use_ingress_http
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_ingress_route53_healthchecks
  to   = module.eu_west_1[0].aws_security_group_rule.use_ingress_route53_healthchecks
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_egress
  to   = module.eu_west_1[0].aws_security_group_rule.use_egress
}

moved {
  from = module.eu_west_1[0].aws_shield_application_layer_automatic_response.actor[0]
  to   = module.eu_west_1[0].aws_shield_application_layer_automatic_response.use[0]
}

moved {
  from = module.eu_west_1[0].aws_ssm_parameter.actor_maintenance_switch
  to   = module.eu_west_1[0].aws_ssm_parameter.use_maintenance_switch
}

moved {
  from = module.eu_west_1[0].aws_wafv2_web_acl_association.actor[0]
  to   = module.eu_west_1[0].aws_wafv2_web_acl_association.use[0]
}

moved {
  from = module.eu_west_1[0].aws_lb_listener.actor_loadbalancer_http_redirect
  to   = module.eu_west_1[0].aws_lb_listener.use_loadbalancer_http_redirect
}

moved {
  from = module.eu_west_1[0].aws_lb_listener_certificate.actor_loadbalancer_live_service_certificate
  to   = module.eu_west_1[0].aws_lb_listener_certificate.use_loadbalancer_live_service_certificate
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_loadbalancer_egress
  to   = module.eu_west_1[0].aws_security_group_rule.use_loadbalancer_egress
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_loadbalancer_ingress
  to   = module.eu_west_1[0].aws_security_group_rule.use_loadbalancer_ingress
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_loadbalancer_ingress_http
  to   = module.eu_west_1[0].aws_security_group_rule.use_loadbalancer_ingress_http
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_loadbalancer_ingress_route53_healthchecks
  to   = module.eu_west_1[0].aws_security_group_rule.use_loadbalancer_ingress_route53_healthchecks
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.api_ecs_service_actor_ingress
  to   = module.eu_west_1[0].aws_security_group_rule.api_ecs_service_use_ingress
}

moved {
  from = module.eu_west_1[0].aws_security_group.actor_loadbalancer
  to   = module.eu_west_1[0].aws_security_group.use_loadbalancer
}

moved {
  from = module.eu_west_1[0].aws_security_group_rule.actor_ecs_service_ingress
  to   = module.eu_west_1[0].aws_security_group_rule.use_ecs_service_ingress
}

moved {
  from = module.eu_west_1[0].aws_ecs_task_definition.actor
  to   = module.eu_west_1[0].aws_ecs_task_definition.use
}

moved {
  from = module.eu_west_1[0].aws_iam_role_policy.actor_permissions_role
  to   = module.eu_west_1[0].aws_iam_role_policy.use_permissions_role
}

moved {
  from = aws_dynamodb_table.actor_users_table
  to   = aws_dynamodb_table.use_users_table
}

moved {
  from = aws_dynamodb_table.actor_codes_table
  to   = aws_dynamodb_table.use_codes_table
}
