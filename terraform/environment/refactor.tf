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
