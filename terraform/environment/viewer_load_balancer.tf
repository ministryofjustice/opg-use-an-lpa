resource "aws_lb_target_group" "viewer" {
  name                 = "${local.environment}-viewer"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.default.id
  deregistration_delay = 0
  depends_on           = [aws_lb.viewer]
  tags                 = local.default_tags
}

resource "aws_lb" "viewer" {
  name               = "${local.environment}-viewer"
  internal           = false
  load_balancer_type = "application"
  subnets            = data.aws_subnet_ids.public.ids
  tags               = local.default_tags

  security_groups = [
    aws_security_group.viewer_loadbalancer.id,
    aws_security_group.viewer_loadbalancer_route53.id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "viewer-${local.environment}"
    enabled = true
  }
}

resource "aws_lb_listener" "viewer_loadbalancer_http_redirect" {
  load_balancer_arn = aws_lb.viewer.arn
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

resource "aws_lb_listener" "viewer_loadbalancer" {
  load_balancer_arn = aws_lb.viewer.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"

  certificate_arn = data.aws_acm_certificate.certificate_view.arn

  default_action {
    target_group_arn = aws_lb_target_group.viewer.arn
    type             = "forward"
  }
}

# maintenance site switching
resource "aws_ssm_parameter" "viewer_maintenance_switch" {
  name            = "${local.environment}_viewer_enable_maintenance"
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
  viewer_path_pattern = {
    field  = "path-pattern"
    values = ["/maintenance"]
  }
  viewer_host_pattern = {
    field  = "host-header"
    values = [aws_route53_record.viewer-use-my-lpa.fqdn]
  }
  viewer_rule_condition = aws_ssm_parameter.viewer_maintenance_switch.value ? local.viewer_host_pattern : local.viewer_path_pattern
}

resource "aws_lb_listener_rule" "viewer_maintenance" {
  listener_arn = aws_lb_listener.viewer_loadbalancer.arn

  action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/html"
      message_body = file("${path.module}/maintenance/viewer_maintenance.html")
      status_code  = "503"
    }
  }

  condition {
    field  = local.viewer_rule_condition.field
    values = local.viewer_rule_condition.values
  }
}

resource "aws_security_group" "viewer_loadbalancer" {
  name        = "${local.environment}-viewer-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress_http" {
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.viewer_loadbalancer.id
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.whitelist.moj_sites
  security_group_id = aws_security_group.viewer_loadbalancer.id
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress_production" {
  count             = local.environment == "production" ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.viewer_loadbalancer.id
}

resource "aws_security_group_rule" "viewer_loadbalancer_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.viewer_loadbalancer.id
}

resource "aws_security_group" "viewer_loadbalancer_route53" {
  name        = "${local.environment}-viewer-loadbalancer-route53"
  description = "Allow Route53 healthchecks"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress_route53_healthchecks" {
  type              = "ingress"
  protocol          = "tcp"
  from_port         = "443"
  to_port           = "443"
  cidr_blocks       = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
  security_group_id = aws_security_group.viewer_loadbalancer_route53.id
  description       = "Loadbalancer ingresss from Route53 healthchecks"
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress_preproduction_ithc" {
  count             = local.environment == "preproduction" ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["54.37.241.156/30", "167.71.136.237/32", ]
  ipv6_cidr_blocks  = ["2001:41d0:800:715::/64"]
  security_group_id = aws_security_group.viewer_loadbalancer.id
}
