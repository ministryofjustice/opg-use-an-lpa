resource "aws_cloudwatch_dashboard" "main" {
  count          = var.create_dashboard ? 1 : 0
  dashboard_name = "${var.environment_name}-${data.aws_region.current.name}-dashboard"
  dashboard_body = templatefile("${path.module}/templates/cw_dashboard_watching.tftpl", {
    region         = data.aws_region.current.name,
    environment    = var.environment_name,
    viewer_alb_arn = local.viewer_alb_arn,
    use_alb_arn    = local.use_alb_arn
  })

  provider = aws.region
}

locals {
  viewer_alb_arn = trimprefix(aws_lb.viewer.arn, "arn:aws:elasticloadbalancing:${data.aws_region.current.name}:${data.aws_caller_identity.current.account_id}:loadbalancer/")
  use_alb_arn    = trimprefix(aws_lb.use.arn, "arn:aws:elasticloadbalancing:${data.aws_region.current.name}:${data.aws_caller_identity.current.account_id}:loadbalancer/")
}

resource "aws_cloudwatch_dashboard" "onelogin" {
  count          = var.create_onelogin_dashboard ? 1 : 0
  dashboard_name = "${var.environment_name}-${data.aws_region.current.name}-onelogin-dashboard"
  dashboard_body = templatefile("${path.module}/templates/cw_dashboard_onelogin.tftpl", {
    region      = data.aws_region.current.name,
    environment = var.environment_name
  })

  provider = aws.region
}
