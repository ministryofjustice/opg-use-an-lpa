module "view_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment_name
  aws_ecs_cluster_name             = module.eu_west_1.ecs_cluster.name
  aws_ecs_service_name             = module.eu_west_1.ecs_services.viewer.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.environment.autoscaling.view.minimum
  ecs_task_autoscaling_maximum     = local.environment.autoscaling.view.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
}
module "use_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment_name
  aws_ecs_cluster_name             = module.eu_west_1.ecs_cluster.name
  aws_ecs_service_name             = module.eu_west_1.ecs_services.actor.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.environment.autoscaling.use.minimum
  ecs_task_autoscaling_maximum     = local.environment.autoscaling.use.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
}
module "api_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment_name
  aws_ecs_cluster_name             = module.eu_west_1.ecs_cluster.name
  aws_ecs_service_name             = module.eu_west_1.ecs_services.api.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.environment.autoscaling.api.minimum
  ecs_task_autoscaling_maximum     = local.environment.autoscaling.api.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
}
module "pdf_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment_name
  aws_ecs_cluster_name             = module.eu_west_1.ecs_cluster.name
  aws_ecs_service_name             = module.eu_west_1.ecs_services.pdf.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.environment.autoscaling.pdf.minimum
  ecs_task_autoscaling_maximum     = local.environment.autoscaling.pdf.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]
}
