resource "aws_lb" "view" {
  name               = "view-${terraform.workspace}"
  internal           = false
  load_balancer_type = "application"
  subnets            = ["${aws_default_subnet.public.*.id}"]

  security_groups = [
    "${aws_security_group.loadbalancer.id}",
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

resource "aws_lb_listener" "loadbalancer" {
  load_balancer_arn = "${aws_lb.view.arn}"
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"
  certificate_arn   = "${aws_acm_certificate_validation.cert.certificate_arn}"

  default_action {
    type = "fixed-response"

    fixed_response {
      content_type = "application/json"
      status_code  = "203"
    }
  }
}

resource "aws_security_group" "loadbalancer" {
  name        = "view-${terraform.workspace}-sg"
  description = "Allow inbound traffic"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "allow_all" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["${formatlist("%s/32", aws_nat_gateway.nat.*.public_ip)}"]
  security_group_id = "${aws_security_group.loadbalancer.id}"
}

data "aws_iam_policy_document" "access_log" {
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
      identifiers = ["${local.target_account}"]
      type        = "AWS"
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
  policy = "${data.aws_iam_policy_document.access_log.json}"
}
