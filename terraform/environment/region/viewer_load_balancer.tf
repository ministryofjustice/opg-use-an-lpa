data "aws_lb" "shared_viewer" {
  count = var.shared_viewer_load_balancer_enabled ? 1 : 0
  name  = "shared-viewer"

  provider = aws.region
}

data "aws_lb_listener" "shared_viewer_https" {
  count             = var.shared_viewer_load_balancer_enabled ? 1 : 0
  load_balancer_arn = data.aws_lb.shared_viewer[0].arn
  port              = 443

  provider = aws.region
}

data "aws_security_group" "shared_viewer_loadbalancer" {
  count = var.shared_viewer_load_balancer_enabled ? 1 : 0
  id    = tolist(data.aws_lb.shared_viewer[0].security_groups)[0]

  provider = aws.region
}

locals {
  viewer_listener_priority         = try(1000 + (tonumber(regex("^\\d+", terraform.workspace)) * 2), null)
  viewer_forward_listener_priority = try(local.viewer_listener_priority + 1, null)
}

resource "aws_shield_application_layer_automatic_response" "viewer" {
  count        = !var.shared_viewer_load_balancer_enabled && var.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.viewer[0].arn
  action       = "BLOCK"
}

resource "aws_lb_target_group" "viewer" {
  name                 = "${var.environment_name}-view"
  port                 = 8080
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.main.id
  deregistration_delay = 0

  health_check {
    path = "/home"
  }

  provider = aws.region
}

resource "aws_lb" "viewer" {
  count                      = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  name                       = "${var.environment_name}-viewer"
  internal                   = false
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = data.aws_subnet.public[*].id
  enable_deletion_protection = var.load_balancer_deletion_protection_enabled

  security_groups = [
    aws_security_group.viewer_loadbalancer[0].id,
    aws_security_group.viewer_loadbalancer_route53[0].id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "viewer-${var.environment_name}"
    enabled = true
  }

  provider = aws.region
}

resource "aws_lb_listener" "viewer_loadbalancer_http_redirect" {
  count             = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  load_balancer_arn = aws_lb.viewer[0].arn
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

resource "aws_lb_listener" "viewer_loadbalancer" {
  count             = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  load_balancer_arn = aws_lb.viewer[0].arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-2019-08"

  certificate_arn = data.aws_acm_certificate.certificate_view.arn

  default_action {
    target_group_arn = aws_lb_target_group.viewer.arn
    type             = "forward"
  }

  provider = aws.region
}

resource "aws_lb_listener_certificate" "viewer_loadbalancer_live_service_certificate" {
  count           = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  listener_arn    = aws_lb_listener.viewer_loadbalancer[0].arn
  certificate_arn = data.aws_acm_certificate.public_facing_certificate_view.arn

  provider = aws.region
}

# redirect root to gov.uk
resource "aws_lb_listener_rule" "redirect_view_root_to_gov" {
  count        = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  listener_arn = aws_lb_listener.viewer_loadbalancer[0].arn
  priority     = 1
  action {
    type = "redirect"

    redirect {
      host        = "www.gov.uk"
      path        = "/view-lasting-power-of-attorney"
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
resource "aws_lb_listener_rule" "rewrite_view_to_live_service_url" {
  count = !var.shared_viewer_load_balancer_enabled && local.is_active_region ? 1 : 0

  listener_arn = aws_lb_listener.viewer_loadbalancer[0].arn
  priority     = 2
  action {
    type = "redirect"

    redirect {
      host        = local.route53_fqdns.public_facing_view
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
        local.route53_fqdns.viewer
      ]
    }
  }

  provider = aws.region
}

moved {
  from = aws_lb_listener_rule.rewrite_view_to_live_service_url
  to   = aws_lb_listener_rule.rewrite_view_to_live_service_url[0]
}

# maintenance site switching
resource "aws_ssm_parameter" "viewer_maintenance_switch" {
  name            = "${var.environment_name}_viewer_enable_maintenance"
  type            = "String"
  value           = "false"
  description     = "values of either 'true' or 'false' only"
  allowed_pattern = "^(true|false)"

  lifecycle {
    ignore_changes = [value]
  }

  provider = aws.region
}

resource "aws_lb_listener_rule" "viewer_maintenance" {
  count        = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  listener_arn = aws_lb_listener.viewer_loadbalancer[0].arn
  priority     = 101 # Specifically set so that maintenance mode scripts can locate the correct rule to modify
  action {
    type = "redirect"

    redirect {
      host        = "maintenance.opg.service.justice.gov.uk"
      path        = "/en-gb/view-a-lasting-power-of-attorney"
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


resource "aws_lb_listener_rule" "viewer_maintenance_welsh" {
  count        = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  listener_arn = aws_lb_listener.viewer_loadbalancer[0].arn
  priority     = 100 # Specifically set so that maintenance mode scripts can locate the correct rule to modify
  action {
    type = "redirect"

    redirect {
      host        = "maintenance.opg.service.justice.gov.uk"
      path        = "/cy/gweld-atwrneiaeth-arhosol"
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


resource "aws_security_group" "viewer_loadbalancer" {
  count       = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  name_prefix = "${var.environment_name}-viewer-loadbalancer"
  description = "View service application load balancer"
  vpc_id      = data.aws_vpc.main.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress_http" {
  count             = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  description       = "Port 80 ingress from the internet to the application load balancer"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.viewer_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress" {
  count             = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = var.moj_sites
  security_group_id = aws_security_group.viewer_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress_public_access" {
  count             = !var.shared_viewer_load_balancer_enabled && var.public_access_enabled ? 1 : 0
  description       = "Port 443 ingress for production from the internet to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.viewer_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "viewer_loadbalancer_egress" {
  count             = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  description       = "Allow any egress from View service load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.viewer_loadbalancer[0].id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_security_group" "viewer_loadbalancer_route53" {
  count       = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  name_prefix = "${var.environment_name}-viewer-loadbalancer-route53"
  description = "View service Route53 healthchecks"
  vpc_id      = data.aws_vpc.main.id

  provider = aws.region
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress_route53_healthchecks" {
  count             = !var.shared_viewer_load_balancer_enabled ? 1 : 0
  description       = "Loadbalancer ingresss from Route53 healthchecks"
  type              = "ingress"
  protocol          = "tcp"
  from_port         = "443"
  to_port           = "443"
  cidr_blocks       = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
  security_group_id = aws_security_group.viewer_loadbalancer_route53[0].id

  provider = aws.region
}

resource "aws_lb_listener_rule" "shared_viewer_root_redirect" {
  count        = var.shared_viewer_load_balancer_enabled ? 1 : 0
  listener_arn = data.aws_lb_listener.shared_viewer_https[0].arn
  priority     = local.viewer_listener_priority

  action {
    type = "redirect"

    redirect {
      host        = "www.gov.uk"
      path        = "/view-lasting-power-of-attorney"
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }

  condition {
    host_header {
      values = [local.route53_fqdns.viewer, local.route53_fqdns.public_facing_view]
    }
  }

  condition {
    path_pattern {
      values = ["/"]
    }
  }

  lifecycle {
    precondition {
      condition     = local.viewer_listener_priority != null && local.viewer_forward_listener_priority <= 50000
      error_message = "The workspace name must produce a valid listener rule priority."
    }
  }

  provider = aws.region
}

resource "aws_lb_listener_rule" "shared_viewer" {
  count        = var.shared_viewer_load_balancer_enabled ? 1 : 0
  listener_arn = data.aws_lb_listener.shared_viewer_https[0].arn
  priority     = local.viewer_forward_listener_priority

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.viewer.arn
  }

  condition {
    host_header {
      values = [local.route53_fqdns.viewer, local.route53_fqdns.public_facing_view]
    }
  }

  lifecycle {
    precondition {
      condition     = local.viewer_forward_listener_priority != null && local.viewer_forward_listener_priority <= 50000
      error_message = "The workspace name must produce a valid listener rule priority."
    }
  }

  provider = aws.region
}

moved {
  from = aws_lb.viewer
  to   = aws_lb.viewer[0]
}

moved {
  from = aws_lb_listener.viewer_loadbalancer_http_redirect
  to   = aws_lb_listener.viewer_loadbalancer_http_redirect[0]
}

moved {
  from = aws_lb_listener.viewer_loadbalancer
  to   = aws_lb_listener.viewer_loadbalancer[0]
}

moved {
  from = aws_lb_listener_certificate.viewer_loadbalancer_live_service_certificate
  to   = aws_lb_listener_certificate.viewer_loadbalancer_live_service_certificate[0]
}

moved {
  from = aws_lb_listener_rule.redirect_view_root_to_gov
  to   = aws_lb_listener_rule.redirect_view_root_to_gov[0]
}

moved {
  from = aws_lb_listener_rule.viewer_maintenance
  to   = aws_lb_listener_rule.viewer_maintenance[0]
}

moved {
  from = aws_lb_listener_rule.viewer_maintenance_welsh
  to   = aws_lb_listener_rule.viewer_maintenance_welsh[0]
}

moved {
  from = aws_security_group.viewer_loadbalancer
  to   = aws_security_group.viewer_loadbalancer[0]
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress_http
  to   = aws_security_group_rule.viewer_loadbalancer_ingress_http[0]
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress
  to   = aws_security_group_rule.viewer_loadbalancer_ingress[0]
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress_public_access
  to   = aws_security_group_rule.viewer_loadbalancer_ingress_public_access[0]
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_egress
  to   = aws_security_group_rule.viewer_loadbalancer_egress[0]
}

moved {
  from = aws_security_group.viewer_loadbalancer_route53
  to   = aws_security_group.viewer_loadbalancer_route53[0]
}

moved {
  from = aws_security_group_rule.viewer_loadbalancer_ingress_route53_healthchecks
  to   = aws_security_group_rule.viewer_loadbalancer_ingress_route53_healthchecks[0]
}
