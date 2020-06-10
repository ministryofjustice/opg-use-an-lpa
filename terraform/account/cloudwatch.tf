resource "aws_cloudwatch_log_group" "use-an-lpa" {
  name              = "use-an-lpa"
  retention_in_days = local.account.retention_in_days

  tags = merge(
    local.default_tags,
    {
      "Name" = "use-an-lpa"
    },
  )
}
