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

output "admin_domain" {
  description = "The URL for the admin interface"
  value       = "https://${var.route_53_fqdns.admin}"
}