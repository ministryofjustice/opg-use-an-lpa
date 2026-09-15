resource "aws_cloudwatch_dashboard" "main" {
  count          = var.create_dashboard ? 1 : 0
  dashboard_name = "${var.environment_name}-${var.region_name}-dashboard"
  dashboard_body = templatefile("${path.module}/templates/cw_dashboard_watching.tftpl", {
    region         = var.region_name,
    environment    = var.environment_name,
    viewer_alb_arn = local.viewer_alb_arn,
    use_alb_arn    = local.use_alb_arn
  })

  provider = aws.region
}

locals {
  viewer_alb_arn = var.shared_load_balancers_enabled ? trimprefix(data.aws_lb.shared_viewer[0].arn, "arn:aws:elasticloadbalancing:${var.region_name}:${data.aws_caller_identity.current.account_id}:loadbalancer/") : trimprefix(aws_lb.viewer[0].arn, "arn:aws:elasticloadbalancing:${var.region_name}:${data.aws_caller_identity.current.account_id}:loadbalancer/")
  use_alb_arn    = var.shared_load_balancers_enabled ? trimprefix(data.aws_lb.shared_actor[0].arn, "arn:aws:elasticloadbalancing:${var.region_name}:${data.aws_caller_identity.current.account_id}:loadbalancer/") : trimprefix(aws_lb.use[0].arn, "arn:aws:elasticloadbalancing:${var.region_name}:${data.aws_caller_identity.current.account_id}:loadbalancer/")
}

resource "aws_cloudwatch_dashboard" "onelogin" {
  count          = var.create_onelogin_dashboard ? 1 : 0
  dashboard_name = "${var.environment_name}-${var.region_name}-onelogin-dashboard"
  dashboard_body = templatefile("${path.module}/templates/cw_dashboard_onelogin.tftpl", {
    ecs_cluster         = aws_ecs_cluster.use_an_lpa.name,
    environment         = var.environment_name,
    region              = var.region_name,
    use_health_check    = module.actor_use_my_lpa.health_check_id,
    use_alb_arn         = local.use_alb_arn,
    viewer_health_check = module.viewer_use_my_lpa.health_check_id,
    viewer_alb_arn      = local.viewer_alb_arn
  })

  provider = aws.region
}
