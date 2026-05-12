data "aws_iam_role" "dynamodb_backup_role" {
  name = "aws_backup_role"
}

#tfsec:ignore:aws-iam-no-policy-wildcards - the iam policy restrictions are being implemented through kms key policies
data "aws_iam_policy_document" "dynamodb_backup_role_policy" {
  statement {
    actions = [
      "kms:Encrypt",
      "kms:CreateGrant",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey"
    ]

    resources = [
      data.aws_kms_key.source_key.arn
    ]
  }
}

resource "aws_iam_policy" "dynamodb_backup_resources" {
  name        = "${var.environment_name}_dynamodb_backup_role_policy"
  description = "Policies for DynamoDB backup role"
  policy      = data.aws_iam_policy_document.dynamodb_backup_role_policy.json
}

resource "aws_iam_role_policy_attachment" "dynamodb_backup_resources" {
  role       = data.aws_iam_role.dynamodb_backup_role.name
  policy_arn = aws_iam_policy.dynamodb_backup_resources.arn
}
