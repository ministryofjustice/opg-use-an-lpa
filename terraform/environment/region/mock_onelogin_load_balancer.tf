data "aws_lb" "shared_mock_onelogin" {
  count = var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  name  = "shared-mock-onelogin"

  provider = aws.region
}

data "aws_lb_listener" "shared_mock_onelogin_https" {
  count             = var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  load_balancer_arn = data.aws_lb.shared_mock_onelogin[0].arn
  port              = 443

  provider = aws.region
}

data "aws_security_group" "shared_mock_onelogin_loadbalancer" {
  count = var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  id    = tolist(data.aws_lb.shared_mock_onelogin[0].security_groups)[0]

  provider = aws.region
}

locals {
  mock_onelogin_listener_priority = try(1000 + tonumber(regex("^\\d+", terraform.workspace)), null)
}

resource "aws_lb_target_group" "mock_onelogin" {
  count                = var.mock_onelogin_enabled ? 1 : 0
  name                 = "${var.environment_name}-mock-onelogin"
  port                 = 8080
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.main.id
  deregistration_delay = 0

  health_check {
    enabled = true
    path    = "/.well-known/openid-configuration"
  }

  provider = aws.region
}

resource "aws_lb" "mock_onelogin" {
  count                      = !var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  name                       = "${var.environment_name}-mock-onelogin"
  internal                   = false
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = data.aws_subnet.public[*].id
  enable_deletion_protection = var.load_balancer_deletion_protection_enabled

  security_groups = [
    aws_security_group.mock_onelogin_loadbalancer[0].id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "mock-onelogin-${var.environment_name}"
    enabled = true
  }

  provider = aws.region
}

resource "aws_lb_listener" "mock_onelogin_loadbalancer_http_redirect" {
  count             = !var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  load_balancer_arn = aws_lb.mock_onelogin[0].arn
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

  provider = aws.region
}

resource "aws_lb_listener" "mock_onelogin_loadbalancer" {
  count             = !var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  load_balancer_arn = aws_lb.mock_onelogin[0].arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-2019-08"

  certificate_arn = data.aws_acm_certificate.certificate_mock_onelogin.arn

  default_action {
    target_group_arn = aws_lb_target_group.mock_onelogin[0].arn
    type             = "forward"
  }

  provider = aws.region
}

resource "aws_lb_listener_rule" "shared_mock_onelogin" {
  count        = var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  listener_arn = data.aws_lb_listener.shared_mock_onelogin_https[0].arn
  priority     = local.mock_onelogin_listener_priority

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.mock_onelogin[0].arn
  }

  condition {
    host_header {
      values = [local.route53_fqdns.mock_onelogin]
    }
  }

  lifecycle {
    precondition {
      condition     = local.mock_onelogin_listener_priority != null && local.mock_onelogin_listener_priority <= 50000
      error_message = "The workspace name must produce a valid listener rule priority."
    }
  }

  provider = aws.region
}

resource "aws_security_group" "mock_onelogin_loadbalancer" {
  count       = !var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  name_prefix = "${var.environment_name}-mock-onelogin-loadbalancer"
  description = "Mock One Login application load balancer"
  vpc_id      = data.aws_vpc.main.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_loadbalancer_port_80_redirect_ingress" {
  count             = !var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  description       = "Port 80 ingress for redirection to port 443"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.mock_onelogin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_loadbalancer_ingress" {
  count             = !var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.mock_onelogin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_loadbalancer_egress" {
  count             = !var.shared_mock_onelogin_load_balancer_enabled && var.mock_onelogin_enabled ? 1 : 0
  description       = "Allow any egress from Mock One Login load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.mock_onelogin_loadbalancer[0].id

  provider = aws.region
}
