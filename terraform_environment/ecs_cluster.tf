resource "aws_ecs_cluster" "use-an-lpa" {
  name = "${terraform.workspace}-use-an-lpa"
  tags = "${local.default_tags}"
}

data "aws_iam_policy_document" "task_role_assume_policy" {
  "statement" {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["ecs-tasks.amazonaws.com"]
      type        = "Service"
    }
  }
}

resource "aws_iam_role" "execution_role" {
  name               = "${terraform.workspace}-execution-role-ecs-cluster"
  assume_role_policy = "${data.aws_iam_policy_document.execution_role_assume_policy.json}"
  tags               = "${local.default_tags}"
}

data "aws_iam_policy_document" "execution_role_assume_policy" {
  "statement" {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["ecs-tasks.amazonaws.com"]
      type        = "Service"
    }
  }
}

resource "aws_iam_role_policy" "execution_role" {
  name   = "${terraform.workspace}_execution_role"
  policy = "${data.aws_iam_policy_document.execution_role.json}"
  role   = "${aws_iam_role.execution_role.id}"
}

data "aws_iam_policy_document" "execution_role" {
  "statement" {
    effect    = "Allow"
    resources = ["*"]

    actions = [
      "ecr:GetAuthorizationToken",
      "ecr:BatchCheckLayerAvailability",
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchGetImage",
      "logs:CreateLogStream",
      "logs:PutLogEvents",
    ]
  }
}
