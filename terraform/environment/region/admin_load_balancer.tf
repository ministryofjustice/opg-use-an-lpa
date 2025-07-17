resource "aws_lb_target_group" "admin" {
  name                 = "${var.environment_name}-admin"
  port                 = 8080
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_default_tags.current.tags.environment-name == "development" ? data.aws_vpc.main.id : data.aws_vpc.default.id
  deregistration_delay = 0

  health_check {
    enabled = true
    path    = "/helloworld"
  }

  depends_on = [aws_lb.admin]

  provider = aws.region
}

moved {
  from = aws_lb_target_group.admin[0]
  to   = aws_lb_target_group.admin
}

resource "aws_lb" "admin" {
  name                       = "${var.environment_name}-admin"
  internal                   = false #tfsec:ignore:aws-elb-alb-not-public - public alb
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = data.aws_subnets.public.ids
  enable_deletion_protection = var.load_balancer_deletion_protection_enabled

  security_groups = [
    aws_security_group.admin_loadbalancer.id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "admin-${var.environment_name}"
    enabled = true
  }

  provider = aws.region
}

moved {
  from = aws_lb.admin[0]
  to   = aws_lb.admin
}

resource "aws_lb_listener" "admin_loadbalancer_http_redirect" {
  load_balancer_arn = aws_lb.admin.arn
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

moved {
  from = aws_lb_listener.admin_loadbalancer_http_redirect[0]
  to   = aws_lb_listener.admin_loadbalancer_http_redirect
}

resource "aws_lb_listener" "admin_loadbalancer" {
  load_balancer_arn = aws_lb.admin.arn
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

moved {
  from = aws_lb_listener.admin_loadbalancer[0]
  to   = aws_lb_listener.admin_loadbalancer
}

resource "aws_lb_listener_certificate" "admin_loadbalancer_live_service_certificate" {
  listener_arn    = aws_lb_listener.admin_loadbalancer.arn
  certificate_arn = data.aws_acm_certificate.public_facing_certificate_use.arn

  provider = aws.region
}

moved {
  from = aws_lb_listener_certificate.admin_loadbalancer_live_service_certificate[0]
  to   = aws_lb_listener_certificate.admin_loadbalancer_live_service_certificate
}

resource "aws_security_group" "admin_loadbalancer" {
  name_prefix = "${var.environment_name}-admin-loadbalancer"
  description = "Admin service application load balancer"
  vpc_id      = data.aws_default_tags.current.tags.environment-name == "development" ? data.aws_vpc.main.id : data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group.admin_loadbalancer[0]
  to   = aws_security_group.admin_loadbalancer
}

resource "aws_security_group_rule" "admin_loadbalancer_port_80_redirect_ingress" {
  description       = "Port 80 ingress for redirection to port 443"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.admin_loadbalancer.id

  provider = aws.region
}

moved {
  from = aws_security_group_rule.admin_loadbalancer_port_80_redirect_ingress[0]
  to   = aws_security_group_rule.admin_loadbalancer_port_80_redirect_ingress
}

resource "aws_security_group_rule" "admin_loadbalancer_ingress" {
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.admin_loadbalancer.id

  provider = aws.region
}

moved {
  from = aws_security_group_rule.admin_loadbalancer_ingress[0]
  to   = aws_security_group_rule.admin_loadbalancer_ingress
}

resource "aws_security_group_rule" "admin_loadbalancer_egress" {
  description       = "Allow any egress from Use service load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-egress-sgr - open egress for load balancers
  security_group_id = aws_security_group.admin_loadbalancer.id

  provider = aws.region
}

moved {
  from = aws_security_group_rule.admin_loadbalancer_egress[0]
  to   = aws_security_group_rule.admin_loadbalancer_egress
}
