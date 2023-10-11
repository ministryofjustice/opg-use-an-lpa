output "ecs_cluster" {
  value = aws_ecs_cluster.use-an-lpa
}

output "ecs_services" {
  # Produce a map of service names to service objects
  value = {
    actor  = aws_ecs_service.actor
    admin  = aws_ecs_service.admin
    api    = aws_ecs_service.api
    pdf    = aws_ecs_service.pdf
    viewer = aws_ecs_service.viewer
  }
}

output "admin_domain" {
  value = "https://${var.route_53_fqdns.admin}"
}