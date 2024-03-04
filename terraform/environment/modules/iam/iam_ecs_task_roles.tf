data "aws_iam_policy_document" "task_role_assume_policy" {
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

resource "aws_iam_role" "admin_task_role" {
  name               = "${var.environment_name}-admin-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}

resource "aws_iam_role" "api_task_role" {
  name               = "${var.environment_name}-api-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}

resource "aws_iam_role" "use_task_role" {
  name               = "${var.environment_name}-actor-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}

moved {
  from = aws_iam_role.actor_task_role
  to   = aws_iam_role.use_task_role
}

resource "aws_iam_role" "viewer_task_role" {
  name               = "${var.environment_name}-viewer-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}

resource "aws_iam_role" "pdf_task_role" {
  name               = "${var.environment_name}-pdf-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}
