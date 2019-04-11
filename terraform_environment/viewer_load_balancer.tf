resource "aws_lb_target_group" "viewer" {
  name                 = "${terraform.workspace}-viewer"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = "${data.aws_vpc.default.id}"
  deregistration_delay = 0
  depends_on           = ["aws_lb.viewer"]
  tags                 = "${local.default_tags}"
}

resource "aws_lb" "viewer" {
  name               = "${terraform.workspace}-viewer"
  internal           = false
  load_balancer_type = "application"
  subnets            = ["${data.aws_subnet.public.*.id}"]
  tags               = "${local.default_tags}"

  security_groups = [
    "${aws_security_group.viewer_loadbalancer.id}",
  ]

  access_logs {
    bucket  = "${data.aws_s3_bucket.access_log.bucket}"
    prefix  = "viewer-${terraform.workspace}"
    enabled = true
  }
}

// TODO - Change the default action to forward to the lb_target_group
resource "aws_lb_listener" "viewer_loadbalancer" {
  load_balancer_arn = "${aws_lb.viewer.arn}"
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"
  certificate_arn   = "${aws_acm_certificate_validation.cert.certificate_arn}"

  default_action {
    target_group_arn = "${aws_lb_target_group.viewer.arn}"
    type             = "forward"
  }
}

resource "aws_security_group" "viewer_loadbalancer" {
  name        = "${terraform.workspace}-viewer-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = "${data.aws_vpc.default.id}"
  tags        = "${local.default_tags}"
}

resource "aws_security_group_rule" "viewer_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.viewer_loadbalancer.id}"
}

resource "aws_security_group_rule" "viewer_loadbalancer_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.viewer_loadbalancer.id}"
}
