data "aws_wafv2_web_acl" "main" {
  name  = "${local.environment.account_name}-web-acl"
  scope = "REGIONAL"
}

resource "aws_wafv2_web_acl_association" "actor" {
  count        = local.environment.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.actor.arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn
}

resource "aws_wafv2_web_acl_association" "viewer" {
  count        = local.environment.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.viewer.arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn
}
