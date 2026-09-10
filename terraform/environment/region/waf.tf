data "aws_wafv2_web_acl" "main" {
  name  = "${var.account_name}-web-acl"
  scope = "REGIONAL"

  provider = aws.region
}

resource "aws_wafv2_web_acl_association" "use" {
  count        = !var.shared_load_balancers_enabled && var.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.use[0].arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn

  provider = aws.region
}

resource "aws_wafv2_web_acl_association" "viewer" {
  count        = !var.shared_load_balancers_enabled && var.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.viewer[0].arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn

  provider = aws.region
}
