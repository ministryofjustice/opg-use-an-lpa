module "view_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = var.environment_name
  aws_ecs_cluster_name             = aws_ecs_cluster.use_an_lpa.name
  aws_ecs_service_name             = aws_ecs_service.viewer.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.view_desired_count
  ecs_task_autoscaling_maximum     = var.autoscaling.view.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  providers = {
    aws.region = aws.region
  }
}

module "use_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = var.environment_name
  aws_ecs_cluster_name             = aws_ecs_cluster.use_an_lpa.name
  aws_ecs_service_name             = aws_ecs_service.use.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.use_desired_count
  ecs_task_autoscaling_maximum     = var.autoscaling.use.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  providers = {
    aws.region = aws.region
  }
}

module "api_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = var.environment_name
  aws_ecs_cluster_name             = aws_ecs_cluster.use_an_lpa.name
  aws_ecs_service_name             = aws_ecs_service.api.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.api_desired_count
  ecs_task_autoscaling_maximum     = var.autoscaling.api.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  providers = {
    aws.region = aws.region
  }
}

module "pdf_ecs_autoscaling" {
  source                           = "./modules/ecs_autoscaling"
  environment                      = var.environment_name
  aws_ecs_cluster_name             = aws_ecs_cluster.use_an_lpa.name
  aws_ecs_service_name             = aws_ecs_service.pdf.name
  ecs_autoscaling_service_role_arn = data.aws_iam_role.ecs_autoscaling_service_role.arn
  ecs_task_autoscaling_minimum     = local.pdf_desired_count
  ecs_task_autoscaling_maximum     = var.autoscaling.pdf.maximum
  max_scaling_alarm_actions        = [aws_sns_topic.cloudwatch_to_pagerduty.arn]

  providers = {
    aws.region = aws.region
  }
}
