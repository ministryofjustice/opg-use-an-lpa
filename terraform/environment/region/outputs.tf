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

output "admin_domain" {
  description = "The URL for the admin interface"
  value       = "https://${var.route_53_fqdns.admin}"
}
