resource "aws_lb_target_group" "admin" {
  count                = local.account.build_admin == true ? 1 : 0
  name                 = "${local.environment}-admin"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.default.id
  deregistration_delay = 0
  depends_on           = [aws_lb.admin[0]]
  tags                 = local.default_tags
}

resource "aws_lb" "admin" {
  count              = local.account.build_admin == true ? 1 : 0
  name               = "${local.environment}-admin"
  internal           = false
  load_balancer_type = "application"
  subnets            = data.aws_subnet_ids.public.ids
  tags               = local.default_tags

  security_groups = [
    aws_security_group.admin_loadbalancer[0].id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "admin-${local.environment}"
    enabled = true
  }
}

resource "aws_lb_listener" "admin_loadbalancer_http_redirect" {
  count             = local.account.build_admin == true ? 1 : 0
  load_balancer_arn = aws_lb.admin[0].arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type = "redirect"

    redirect {
      port        = 443
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

resource "aws_lb_listener" "admin_loadbalancer" {
  count             = local.account.build_admin == true ? 1 : 0
  load_balancer_arn = aws_lb.admin[0].arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"

  certificate_arn = data.aws_acm_certificate.certificate_admin.arn

  default_action {
    target_group_arn = aws_lb_target_group.admin[0].arn
    type             = "forward"
  }
}

resource "aws_lb_listener_certificate" "admin_loadbalancer_live_service_certificate" {
  count           = local.account.build_admin == true ? 1 : 0
  listener_arn    = aws_lb_listener.admin_loadbalancer[0].arn
  certificate_arn = data.aws_acm_certificate.public_facing_certificate_use.arn
}


resource "aws_security_group" "admin_loadbalancer" {
  count       = local.account.build_admin == true ? 1 : 0
  name        = "${local.environment}-admin-loadbalancer"
  description = "Admin service application load balancer"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "admin_loadbalancer_ingress" {
  count             = local.account.build_admin == true ? 1 : 0
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.whitelist.moj_sites
  security_group_id = aws_security_group.admin_loadbalancer[0].id
}

resource "aws_security_group_rule" "admin_loadbalancer_egress" {
  count             = local.account.build_admin == true ? 1 : 0
  description       = "Allow any egress from Use service load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.admin_loadbalancer[0].id
}
