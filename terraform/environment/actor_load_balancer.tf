resource "aws_lb_target_group" "actor" {
  name                 = "${local.environment}-actor"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.default.id
  deregistration_delay = 0
  depends_on           = [aws_lb.actor]
  tags                 = local.default_tags
}

resource "aws_lb" "actor" {
  name                       = "${local.environment}-actor"
  internal                   = false #tfsec:ignore:AWS005 - public alb
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = data.aws_subnet_ids.public.ids
  tags                       = local.default_tags

  security_groups = [
    aws_security_group.actor_loadbalancer.id,
    aws_security_group.actor_loadbalancer_route53.id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "actor-${local.environment}"
    enabled = true
  }
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
}

resource "aws_lb_listener" "actor_loadbalancer" {
  load_balancer_arn = aws_lb.actor.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-2019-08" #forward security
  # ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06" #current
  # ssl_policy        = "ELBSecurityPolicy-TLS-1-2-2017-01" #tls

  certificate_arn = data.aws_acm_certificate.certificate_use.arn

  default_action {
    target_group_arn = aws_lb_target_group.actor.arn
    type             = "forward"
  }
}

resource "aws_lb_listener_certificate" "actor_loadbalancer_live_service_certificate" {
  listener_arn    = aws_lb_listener.actor_loadbalancer.arn
  certificate_arn = data.aws_acm_certificate.public_facing_certificate_use.arn
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
}

# rewrite to live service url
resource "aws_lb_listener_rule" "rewrite_use_to_live_service_url" {
  listener_arn = aws_lb_listener.actor_loadbalancer.arn
  priority     = 2
  action {
    type = "redirect"

    redirect {
      host        = aws_route53_record.public_facing_use_lasting_power_of_attorney.fqdn
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
        aws_route53_record.actor-use-my-lpa.fqdn
      ]
    }
  }
}

# maintenance site switching
resource "aws_ssm_parameter" "actor_maintenance_switch" {
  name            = "${local.environment}_actor_enable_maintenance"
  type            = "String"
  value           = "false"
  description     = "values of either 'true' or 'false' only"
  allowed_pattern = "^(true|false)"
  overwrite       = true
  lifecycle {
    ignore_changes = [value]
  }
}

resource "aws_lb_listener_rule" "actor_maintenance" {
  listener_arn = aws_lb_listener.actor_loadbalancer.arn
  priority     = 101 # Specifically set so that maintenance mode scripts can locate the correct rule to modify
  action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/html"
      message_body = file("${path.module}/maintenance/actor_maintenance.html")
      status_code  = "503"
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
}

resource "aws_lb_listener_rule" "actor_maintenance_welsh" {
  listener_arn = aws_lb_listener.actor_loadbalancer.arn
  priority     = 100 # Specifically set so that maintenance mode scripts can locate the correct rule to modify
  action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/html"
      message_body = file("${path.module}/maintenance/actor_maintenance_welsh.html")
      status_code  = "503"
    }
  }
  condition {
    path_pattern {
      values = ["/maintenance-cy"]
    }
  }
  lifecycle {
    ignore_changes = [
      # Ignore changes to the condition as this is modified by a script
      # when putting the service into maintenance mode.
      condition,
    ]
  }
}



resource "aws_security_group" "actor_loadbalancer" {
  name_prefix = "${local.environment}-actor-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_http" {
  description       = "Port 80 ingress from the internet to the application load balancer"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS006 - open ingress for load balancers
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress" {
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.whitelist.moj_sites
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_production" {
  description       = "Port 443 ingress for production from the internet to the application load balancer"
  count             = local.environment == "production" ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS006 - open ingress for load balancers
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group_rule" "actor_loadbalancer_egress" {
  description       = "Allow any egress from Use service load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS007 - open egress for load balancers
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group" "actor_loadbalancer_route53" {
  name_prefix = "${local.environment}-actor-loadbalancer-route53"
  description = "Allow Route53 healthchecks"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_route53_healthchecks" {
  description       = "Loadbalancer ingresss from Route53 healthchecks"
  type              = "ingress"
  protocol          = "tcp"
  from_port         = "443"
  to_port           = "443"
  cidr_blocks       = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
  security_group_id = aws_security_group.actor_loadbalancer_route53.id
}
