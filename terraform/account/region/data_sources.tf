data "aws_caller_identity" "current" {
  provider = aws.region
}

data "aws_caller_identity" "management" {
  provider = aws.management
}
