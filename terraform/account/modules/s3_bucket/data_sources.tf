data "aws_region" "current" {}

data "aws_s3_bucket" "access_logging" {
  bucket = "s3-access-logs-opg-opg-use-an-lpa-${var.account_name}-${data.aws_region.current.name}"
}
