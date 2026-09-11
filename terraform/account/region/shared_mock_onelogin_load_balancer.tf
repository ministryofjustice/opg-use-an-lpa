module "allow_list" {
  source = "git@github.com:ministryofjustice/opg-terraform-aws-moj-ip-allow-list.git?ref=v3.4.2"
}

resource "aws_security_group" "shared_mock_onelogin_loadbalancer" {
  count       = var.account_name == "development" ? 1 : 0
  name_prefix = "shared-mock-onelogin-loadbalancer"
  description = "Shared Mock One Login application load balancer"
  vpc_id      = module.network.vpc.id

  provider = aws.region
}

resource "aws_security_group_rule" "shared_mock_onelogin_loadbalancer_port_80_ingress" {
  count             = var.account_name == "development" ? 1 : 0
  description       = "Port 80 ingress for redirection to port 443"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = module.allow_list.moj_sites
  security_group_id = aws_security_group.shared_mock_onelogin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "shared_mock_onelogin_loadbalancer_port_443_ingress" {
  count             = var.account_name == "development" ? 1 : 0
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.allow_list.moj_sites
  security_group_id = aws_security_group.shared_mock_onelogin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_security_group_rule" "shared_mock_onelogin_loadbalancer_egress" {
  count             = var.account_name == "development" ? 1 : 0
  description       = "Allow any egress from the shared Mock One Login load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.shared_mock_onelogin_loadbalancer[0].id

  provider = aws.region
}

resource "aws_lb" "shared_mock_onelogin" {
  count                      = var.account_name == "development" ? 1 : 0
  name                       = "shared-mock-onelogin"
  internal                   = false
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = module.network.public_subnets[*].id
  enable_deletion_protection = true
  security_groups            = [aws_security_group.shared_mock_onelogin_loadbalancer[0].id]

  access_logs {
    bucket  = aws_s3_bucket.access_log.bucket
    prefix  = "shared-mock-onelogin"
    enabled = true
  }

  provider = aws.region
}

resource "aws_lb_listener" "shared_mock_onelogin_http_redirect" {
  count             = var.account_name == "development" ? 1 : 0
  load_balancer_arn = aws_lb.shared_mock_onelogin[0].arn
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

resource "aws_lb_listener" "shared_mock_onelogin_https" {
  count             = var.account_name == "development" ? 1 : 0
  load_balancer_arn = aws_lb.shared_mock_onelogin[0].arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-2019-08"
  certificate_arn   = aws_acm_certificate.certificate_mock_onelogin.arn

  default_action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/plain"
      message_body = "Not found"
      status_code  = "404"
    }
  }

  provider = aws.region
}
