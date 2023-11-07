resource "aws_kms_key" "this" {
  description             = var.key_description
  deletion_window_in_days = var.deletion_window_in_days
  enable_key_rotation     = var.enable_key_rotation
  policy                  = var.key_policy
  multi_region            = true

  provider = aws.primary
}

resource "aws_kms_alias" "primary_alias" {
  name          = "alias/${var.key_alias}"
  target_key_id = aws_kms_key.this.key_id

  provider = aws.primary
}

resource "aws_kms_replica_key" "this" {
  description             = var.key_description
  deletion_window_in_days = var.deletion_window_in_days
  primary_key_arn         = aws_kms_key.this.arn
  policy                  = var.key_policy

  provider = aws.secondary
}

resource "aws_kms_alias" "secondary_alias" {
  name          = "alias/${var.key_alias}"
  target_key_id = aws_kms_replica_key.this.key_id

  provider = aws.secondary
}
