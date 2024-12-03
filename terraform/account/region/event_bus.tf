module "event_bus" {
  source            = "./modules/event_bus"
  environment_name  = var.environment_name
  event_bus_enabled = var.event_bus_enabled
  current_region    = data.aws_region.current.name
  providers = {
    aws.region = aws.region
  }
}
