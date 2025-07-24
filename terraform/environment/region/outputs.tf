output "ecs_cluster" {
  description = "The ECS cluster object"
  value       = aws_ecs_cluster.use_an_lpa
}

output "ecs_services" {
  description = "Objects containing the ECS services"
  value = {
    actor         = aws_ecs_service.use
    admin         = aws_ecs_service.admin
    api           = aws_ecs_service.api
    pdf           = aws_ecs_service.pdf
    viewer        = aws_ecs_service.viewer
    mock_onelogin = aws_ecs_service.mock_onelogin
  }
}

output "albs" {
  description = "Objects containing the ALBs"
  value = {
    actor         = aws_lb.use
    admin         = aws_lb.admin
    viewer        = aws_lb.viewer
    mock_onelogin = aws_lb.mock_onelogin
  }
}

output "security_group_names" {
  description = "Security group names"
  value = {
    actor_loadbalancer         = aws_security_group.use_loadbalancer.name
    viewer_loadbalancer        = aws_security_group.viewer_loadbalancer.name
    mock_onelogin_loadbalancer = var.mock_onelogin_enabled ? aws_security_group.mock_onelogin_loadbalancer[0].name : null
  }
}

output "security_group_ids" {
  description = "Security group ids"
  value = {
    actor_loadbalancer         = aws_security_group.use_loadbalancer.id
    viewer_loadbalancer        = aws_security_group.viewer_loadbalancer.id
    mock_onelogin_loadbalancer = var.mock_onelogin_enabled ? aws_security_group.mock_onelogin_loadbalancer[0].name : null
  }
}

output "route53_fqdns" {
  description = "The FQDNs for the various services"
  value = {
    public_facing_view = local.route53_fqdns.public_facing_view
    public_facing_use  = local.route53_fqdns.public_facing_use
    admin              = local.route53_fqdns.admin
    use                = local.route53_fqdns.use
    viewer             = local.route53_fqdns.viewer
    mock_onelogin      = local.route53_fqdns.mock_onelogin
  }
}


output "receive_events_bus_arn" {
  description = "The ARN of the event bus created by the event_bus module."
  value       = module.event_bus.receive_events_bus_arn
}

output "receive_events_sqs_queue_arn" {
  description = "The name of the SQS queue created by the event_bus module."
  value       = module.event_bus.receive_events_sqs_queue_arn
}
output "receive_events_sqs_queue_name" {
  description = "SQS queue name from the event_bus module"
  value       = module.event_bus.receive_events_sqs_queue_name
}

output "vpc_id" {
  value = data.aws_default_tags.current.tags.account-name != "production" ? data.aws_vpc.main.id : data.aws_vpc.default.id
}
