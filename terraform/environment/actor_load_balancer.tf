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
  name               = "${local.environment}-actor"
  internal           = false
  load_balancer_type = "application"
  subnets            = data.aws_subnet_ids.public.ids
  tags               = local.default_tags

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
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"

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

locals {
  actor_path_pattern = {
    field  = "path-pattern"
    values = ["/maintenance"]
  }
  actor_host_pattern = {
    field  = "host-header"
    values = [aws_route53_record.actor-use-my-lpa.fqdn]
  }
  actor_rule_condition = aws_ssm_parameter.actor_maintenance_switch.value ? local.actor_host_pattern : local.actor_path_pattern
}

resource "aws_lb_listener_rule" "actor_maintenance" {
  listener_arn = aws_lb_listener.actor_loadbalancer.arn

  action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/html"
      message_body = file("${path.module}/maintenance/actor_maintenance.html")
      status_code  = "503"
    }
  }

  condition {
    field  = local.actor_rule_condition.field
    values = local.actor_rule_condition.values
  }
}


resource "aws_security_group" "actor_loadbalancer" {
  name        = "${local.environment}-actor-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_http" {
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.whitelist.moj_sites
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_production" {
  count             = local.environment == "production" ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group_rule" "actor_loadbalancer_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.actor_loadbalancer.id
}

resource "aws_security_group" "actor_loadbalancer_route53" {
  name        = "${local.environment}-actor-loadbalancer-route53"
  description = "Allow Route53 healthchecks"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_route53_healthchecks" {
  type              = "ingress"
  protocol          = "tcp"
  from_port         = "443"
  to_port           = "443"
  cidr_blocks       = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
  security_group_id = aws_security_group.actor_loadbalancer_route53.id
  description       = "Loadbalancer ingresss from Route53 healthchecks"
}
