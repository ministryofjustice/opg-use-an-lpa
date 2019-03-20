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

resource "aws_lb_listener" "view_loadbalancer" {
  load_balancer_arn = "${aws_lb.view.arn}"
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"
  certificate_arn   = "${aws_acm_certificate_validation.cert.certificate_arn}"

  default_action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/plain"
      message_body = "Fixed response content"
      status_code  = "200"
    }
  }
}

resource "aws_security_group" "view_loadbalancer" {
  name        = "view-${terraform.workspace}-sg"
  description = "Allow inbound traffic"
  vpc_id      = "${aws_default_vpc.default.id}"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "view_loadbalancer" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.view_loadbalancer.id}"
}

data "aws_iam_policy_document" "view_loadbalancer" {
  statement {
    sid = "accessLogBucketAccess"

    resources = [
      "${aws_s3_bucket.access_log.arn}",
      "${aws_s3_bucket.access_log.arn}/*",
    ]

    effect  = "Allow"
    actions = ["s3:PutObject"]

    principals {
      // AWS docs, Account ID that corresponds to the region for your load balancer and bucket.
      // https://docs.aws.amazon.com/elasticloadbalancing/latest/application/load-balancer-access-logs.html
      identifiers = ["652711504416"]

      type = "AWS"
    }
  }
}

resource "aws_s3_bucket" "access_log" {
  bucket = "opg-use-an-lpa-${terraform.workspace}-lb-access-log"
  acl    = "private"

  server_side_encryption_configuration {
    "rule" {
      "apply_server_side_encryption_by_default" {
        sse_algorithm = "aws:kms"
      }
    }
  }
}

resource "aws_s3_bucket_policy" "access_log" {
  bucket = "${aws_s3_bucket.access_log.id}"
  policy = "${data.aws_iam_policy_document.view_loadbalancer.json}"
}
