data "aws_lb" "shared_admin" {
  count = var.shared_load_balancers_enabled ? 1 : 0
  name  = "shared-admin"

  provider = aws.region
}

data "aws_lb_listener" "shared_admin_https" {
  count             = var.shared_load_balancers_enabled ? 1 : 0
  load_balancer_arn = data.aws_lb.shared_admin[0].arn
  port              = 443

  provider = aws.region
}

locals {
  admin_listener_priority = try(1000 + tonumber(regex("^\\d+", terraform.workspace)), null)
}

resource "aws_lb_target_group" "admin" {
  name                 = "${var.environment_name}-admin"
  port                 = 8080
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.main.id
  deregistration_delay = 0

  health_check {
    enabled = true
    path    = "/helloworld"
  }

  provider = aws.region
}

moved {
  from = aws_lb_target_group.admin[0]
  to   = aws_lb_target_group.admin
}

resource "aws_lb" "admin" {
  count                      = !var.shared_load_balancers_enabled ? 1 : 0
  name                       = "${var.environment_name}-admin"
  internal                   = false
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = data.aws_subnet.public[*].id
  enable_deletion_protection = var.load_balancer_deletion_protection_enabled

  security_groups = [
    aws_security_group.admin_loadbalancer[0].id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "admin-${var.environment_name}"
    enabled = true
  }

  provider = aws.region
}

resource "aws_lb_listener" "admin_loadbalancer_http_redirect" {
  count             = !var.shared_load_balancers_enabled ? 1 : 0
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

  provider = aws.region
}

resource "aws_lb_listener" "admin_loadbalancer" {
  count             = !var.shared_load_balancers_enabled ? 1 : 0
  load_balancer_arn = aws_lb.admin[0].arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-2019-08"

  certificate_arn = data.aws_acm_certificate.certificate_admin.arn

  default_action {
    type = "authenticate-oidc"
    authenticate_oidc {
      authentication_request_extra_params = {}
      authorization_endpoint              = "${var.admin_cognito.user_pool_domain_name}/oauth2/authorize"
      client_id                           = var.admin_cognito.id
      client_secret                       = var.admin_cognito.user_pool_client_secret
      issuer                              = "https://cognito-idp.eu-west-1.amazonaws.com/${var.admin_cognito.user_pool_id}"
      on_unauthenticated_request          = "authenticate"
      scope                               = "openid"
      session_cookie_name                 = "AWSELBAuthSessionCookie"
      session_timeout                     = var.admin_cognito.user_pool_id_token_validity
      token_endpoint                      = "${var.admin_cognito.user_pool_domain_name}/oauth2/token"
      user_info_endpoint                  = "${var.admin_cognito.user_pool_domain_name}/oauth2/userInfo"
    }
  }

  default_action {
    target_group_arn = aws_lb_target_group.admin.arn
    type             = "forward"
  }

  provider = aws.region
}

resource "aws_lb_listener_certificate" "admin_loadbalancer_live_service_certificate" {
  count           = !var.shared_load_balancers_enabled ? 1 : 0
  listener_arn    = aws_lb_listener.admin_loadbalancer[0].arn
  certificate_arn = data.aws_acm_certificate.public_facing_certificate_use.arn

  provider = aws.region
}

resource "aws_security_group" "admin_loadbalancer" {
  count       = !var.shared_load_balancers_enabled ? 1 : 0
  name_prefix = "${var.environment_name}-admin-loadbalancer"
  description = "Admin service application load balancer"
  vpc_id      = data.aws_vpc.main.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_security_group_rule" "admin_loadbalancer_port_80_redirect_ingress" {
  count             = !var.shared_load_balancers_enabled ? 1 : 0
  description       = "Port 80 ingress for redirection to port 443"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.admin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "admin_loadbalancer_ingress" {
  count             = !var.shared_load_balancers_enabled ? 1 : 0
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.admin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "admin_loadbalancer_egress" {
  count             = !var.shared_load_balancers_enabled ? 1 : 0
  description       = "Allow any egress from Use service load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.admin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_lb_listener_rule" "shared_admin" {
  count        = var.shared_load_balancers_enabled ? 1 : 0
  listener_arn = data.aws_lb_listener.shared_admin_https[0].arn
  priority     = local.admin_listener_priority

  action {
    type = "authenticate-oidc"
    authenticate_oidc {
      authentication_request_extra_params = {}
      authorization_endpoint              = "${var.admin_cognito.user_pool_domain_name}/oauth2/authorize"
      client_id                           = var.admin_cognito.id
      client_secret                       = var.admin_cognito.user_pool_client_secret
      issuer                              = "https://cognito-idp.eu-west-1.amazonaws.com/${var.admin_cognito.user_pool_id}"
      on_unauthenticated_request          = "authenticate"
      scope                               = "openid"
      session_cookie_name                 = "AWSELBAuthSessionCookie"
      session_timeout                     = var.admin_cognito.user_pool_id_token_validity
      token_endpoint                      = "${var.admin_cognito.user_pool_domain_name}/oauth2/token"
      user_info_endpoint                  = "${var.admin_cognito.user_pool_domain_name}/oauth2/userInfo"
    }
  }

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.admin.arn
  }

  condition {
    host_header {
      values = [local.route53_fqdns.admin]
    }
  }

  lifecycle {
    precondition {
      condition     = local.admin_listener_priority != null && local.admin_listener_priority <= 50000
      error_message = "The workspace name must produce a valid listener rule priority."
    }
  }

  provider = aws.region
}
