module "view_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = local.environment
  aws_ecs_cluster_name             = aws_ecs_cluster.use-an-lpa.name
  aws_ecs_service_name             = aws_ecs_service.viewer.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.account.autoscaling.view.minimum
  ecs_task_autoscaling_maximum     = local.account.autoscaling.view.maximum
}
