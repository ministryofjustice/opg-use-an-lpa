resource "aws_shield_application_layer_automatic_response" "actor" {
  count        = var.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.actor.arn
  action       = "COUNT"

  provider = aws.region
}

resource "aws_lb_target_group" "actor" {
  name                 = "${var.environment_name}-actor"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.default.id
  deregistration_delay = 0
  depends_on           = [aws_lb.actor]

  provider = aws.region
}

resource "aws_lb" "actor" {
  name                       = "${var.environment_name}-actor"
  internal                   = false #tfsec:ignore:aws-elb-alb-not-public - Intentionally public facing
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = data.aws_subnets.public.ids

  enable_deletion_protection = var.load_balancer_deletion_protection_enabled

  security_groups = [
    aws_security_group.actor_loadbalancer.id,
    aws_security_group.actor_loadbalancer_route53.id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "actor-${var.environment_name}"
    enabled = true
  }

  provider = aws.region
}

resource "aws_lb_listener" "actor_loadbalancer_http_redirect" {
  load_balancer_arn = aws_lb.actor.arn
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

resource "aws_lb_listener" "actor_loadbalancer" {
  load_balancer_arn = aws_lb.actor.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-2019-08"

  certificate_arn = data.aws_acm_certificate.certificate_use.arn

  default_action {
    target_group_arn = aws_lb_target_group.actor.arn
    type             = "forward"
  }

  provider = aws.region
}

resource "aws_lb_listener_certificate" "actor_loadbalancer_live_service_certificate" {
  listener_arn    = aws_lb_listener.actor_loadbalancer.arn
  certificate_arn = data.aws_acm_certificate.public_facing_certificate_use.arn

  provider = aws.region
}

# redirect root to gov.uk
resource "aws_lb_listener_rule" "redirect_use_root_to_gov" {
  listener_arn = aws_lb_listener.actor_loadbalancer.arn
  priority     = 1
  action {
    type = "redirect"

    redirect {
      host        = "www.gov.uk"
      path        = "/use-lasting-power-of-attorney"
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }

  condition {
    path_pattern {
      values = [
        "/",
      ]
    }
  }

  provider = aws.region
}

# rewrite to live service url
resource "aws_lb_listener_rule" "rewrite_use_to_live_service_url" {
  count = local.route53_fqdns.public_facing_use != "" ? 1 : 0

  listener_arn = aws_lb_listener.actor_loadbalancer.arn
  priority     = 2
  action {
    type = "redirect"

    redirect {
      host        = local.route53_fqdns.public_facing_use
      path        = "/#{path}"
      query       = "#{query}"
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
  condition {
    host_header {
      values = [
        local.route53_fqdns.actor
      ]
    }
  }

  provider = aws.region
}

moved {
  from = aws_lb_listener_rule.rewrite_use_to_live_service_url
  to   = aws_lb_listener_rule.rewrite_use_to_live_service_url[0]
}

# maintenance site switching
resource "aws_ssm_parameter" "actor_maintenance_switch" {
  name            = "${var.environment_name}_actor_enable_maintenance"
  type            = "String"
  value           = "false"
  description     = "values of either 'true' or 'false' only"
  allowed_pattern = "^(true|false)"
  overwrite       = true
  lifecycle {
    ignore_changes = [value]
  }

  provider = aws.region
}

resource "aws_lb_listener_rule" "actor_maintenance" {
  listener_arn = aws_lb_listener.actor_loadbalancer.arn
  priority     = 101 # Specifically set so that maintenance mode scripts can locate the correct rule to modify
  action {
    type = "redirect"

    redirect {
      host        = "maintenance.opg.service.justice.gov.uk"
      path        = "/en-gb/use-a-lasting-power-of-attorney"
      query       = ""
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_302"
    }
  }
  condition {
    path_pattern {
      values = ["/maintenance"]
    }
  }
  lifecycle {
    ignore_changes = [
      # Ignore changes to the condition as this is modified by a script
      # when putting the service into maintenance mode.
      condition,
    ]
  }

  provider = aws.region
}

resource "aws_lb_listener_rule" "actor_maintenance_welsh" {
  listener_arn = aws_lb_listener.actor_loadbalancer.arn
  priority     = 100 # Specifically set so that maintenance mode scripts can locate the correct rule to modify
  action {
    type = "redirect"

    redirect {
      host        = "maintenance.opg.service.justice.gov.uk"
      path        = "/cy/defnyddio-atwrneiaeth-arhosol"
      query       = ""
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_302"
    }
  }
  condition {
    path_pattern {
      values = ["/cy/maintenance"]
    }
  }
  lifecycle {
    ignore_changes = [
      # Ignore changes to the condition as this is modified by a script
      # when putting the service into maintenance mode.
      condition,
    ]
  }

  provider = aws.region
}



resource "aws_security_group" "actor_loadbalancer" {
  name_prefix = "${var.environment_name}-actor-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.default.id

  provider = aws.region
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_http" {
  description       = "Port 80 ingress from the internet to the application load balancer"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-ingress-sgr - open ingress for load balancers
  security_group_id = aws_security_group.actor_loadbalancer.id

  provider = aws.region
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress" {
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.actor_loadbalancer.id

  provider = aws.region
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_public_access" {
  count             = var.public_access_enabled ? 1 : 0
  description       = "Port 443 ingress for production from the internet to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-ingress-sgr - open ingress for load balancers
  security_group_id = aws_security_group.actor_loadbalancer.id

  provider = aws.region
}

resource "aws_security_group_rule" "actor_loadbalancer_egress" {
  description       = "Allow any egress from Use service load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-egress-sgr - open egress for load balancers
  security_group_id = aws_security_group.actor_loadbalancer.id

  provider = aws.region
}

resource "aws_security_group" "actor_loadbalancer_route53" {
  name_prefix = "${var.environment_name}-actor-loadbalancer-route53"
  description = "Allow Route53 healthchecks"
  vpc_id      = data.aws_vpc.default.id

  provider = aws.region
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_route53_healthchecks" {
  description       = "Loadbalancer ingresss from Route53 healthchecks"
  type              = "ingress"
  protocol          = "tcp"
  from_port         = "443"
  to_port           = "443"
  cidr_blocks       = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
  security_group_id = aws_security_group.actor_loadbalancer_route53.id

  provider = aws.region
}
