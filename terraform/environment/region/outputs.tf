output "ecs_cluster" {
  description = "The ECS cluster object"
  value       = aws_ecs_cluster.use_an_lpa
}

output "ecs_services" {
  description = "Objects containing the ECS services"
  value = {
    actor  = aws_ecs_service.actor
    admin  = aws_ecs_service.admin
    api    = aws_ecs_service.api
    pdf    = aws_ecs_service.pdf
    viewer = aws_ecs_service.viewer
  }
}

output "albs" {
  description = "Objects containing the ALBs"
  value = {
    actor  = aws_lb.actor
    admin  = aws_lb.admin
    viewer = aws_lb.viewer
  }
}

output "security_group_names" {
  description = "Security group names"
  value = {
    actor_loadbalancer  = aws_security_group.actor_loadbalancer.name
    viewer_loadbalancer = aws_security_group.viewer_loadbalancer.name
  }
}

output "route53_fqdns" {
  description = "The FQDNs for the various services"
  value = {
    public_facing_view = local.route53_fqdns.public_facing_view
    public_facing_use  = local.route53_fqdns.public_facing_use
    admin              = local.route53_fqdns.admin
    actor              = local.route53_fqdns.actor
    viewer             = local.route53_fqdns.viewer
  }
}
