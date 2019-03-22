resource "aws_lb" "view" {
  name               = "view-${terraform.workspace}"
  internal           = false
  load_balancer_type = "application"
  subnets            = ["${aws_default_subnet.public.*.id}"]

  security_groups = [
    "${aws_security_group.view_loadbalancer.id}",
  ]

  access_logs {
    bucket  = "${aws_s3_bucket.access_log.bucket}"
    prefix  = "view-${terraform.workspace}"
    enabled = true
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_lb_target_group" "view" {
  name                 = "view-loadbalancer-group"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = "${aws_default_vpc.default.id}"
  deregistration_delay = 0
  tags                 = "${local.default_tags}"
}

// TODO - Change the default action to forward to the lb_target_group
resource "aws_lb_listener" "view_loadbalancer" {
  load_balancer_arn = "${aws_lb.view.arn}"
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"
  certificate_arn   = "${aws_acm_certificate_validation.cert.certificate_arn}"

  default_action {
    target_group_arn = "${aws_lb_target_group.view.arn}"
    type             = "forward"
  }
}

resource "aws_security_group" "view_loadbalancer" {
  name        = "view-${terraform.workspace}-sg"
  description = "Allow inbound traffic"
  vpc_id      = "${aws_default_vpc.default.id}"
}

resource "aws_security_group_rule" "view_loadbalancer" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.view_loadbalancer.id}"
}

resource "aws_security_group_rule" "view_loadbalancer_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.view_loadbalancer.id}"
}
