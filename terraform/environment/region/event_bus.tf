module "event_bus" {
  source                     = "./modules/event_bus"
  environment_name           = var.environment_name
  event_bus_enabled          = var.event_bus_enabled
  current_region             = data.aws_region.current.region
  receive_account_ids        = var.receive_account_ids
  queue_visibility_timeout   = local.queue_visibility_timeout
  event_reciever_kms_key_arn = var.event_reciever_kms_key_arn
  providers = {
    aws.region = aws.region
  }
}
