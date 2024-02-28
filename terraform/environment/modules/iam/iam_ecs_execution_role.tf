resource "aws_iam_role" "execution_role" {
  name               = "${var.environment_name}-execution-role-ecs-cluster"
  assume_role_policy = data.aws_iam_policy_document.execution_role_assume_policy.json
}

data "aws_iam_policy_document" "execution_role_assume_policy" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["ecs-tasks.amazonaws.com"]
      type        = "Service"
    }

    condition {
      test     = "StringEquals"
      variable = "aws:SourceAccount"
      values   = [data.aws_caller_identity.current.account_id]
    }
  }
}
