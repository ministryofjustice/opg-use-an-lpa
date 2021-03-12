locals {
  images = [
    "${local.environment}/clsf_to_sqs",
    "${local.environment}/ship_to_opg_metrics",
  ]
}

resource "aws_ecr_repository" "lambda" {
  for_each = toset(local.images)
  name     = each.value
  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_ecr_repository_policy" "lambda" {
  for_each   = toset(values(aws_ecr_repository.lambda)[*].name)
  policy     = data.aws_iam_policy_document.lambda_ecr_access.json
  repository = each.value
}

resource "aws_ecr_lifecycle_policy" "lambda" {
  for_each   = toset(values(aws_ecr_repository.lambda)[*].name)
  repository = each.value

  policy = <<EOF
{
    "rules": [
        {
            "rulePriority": 1,
            "description": "Expire images more than 10 production deployments ago",
            "selection": {
                "tagStatus": "tagged",
                "tagPrefixList": ["master", "main"],
                "countType": "imageCountMoreThan",
                "countNumber": 10
            },
            "action": {
                "type": "expire"
            }
        },
        {
            "rulePriority": 2,
            "description": "Expire untagged images quickly",
            "selection": {
                "tagStatus": "untagged",
                "countType": "sinceImagePushed",
                "countUnit": "days",
                "countNumber": 1
            },
            "action": {
                "type": "expire"
            }
        },
        {
            "rulePriority": 3,
            "description": "Expire all non-production tagged images older than 14 days",
            "selection": {
                "tagStatus": "any",
                "countType": "sinceImagePushed",
                "countUnit": "days",
                "countNumber": 14
            },
            "action": {
                "type": "expire"
            }
        }
    ]
}
EOF
}


data "aws_iam_policy_document" "lambda_ecr_access" {
  statement {
    sid    = "UseAnLPALambdaAccountAccess"
    effect = "Allow"

    actions = [
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchCheckLayerAvailability",
      "ecr:BatchGetImage",
      "ecr:DescribeImages",
      "ecr:DescribeRepositories",
      "ecr:ListImages",
    ]

    principals {
      identifiers = [
        "arn:aws:iam::${local.account.account_id}:root",
      ]

      type = "AWS"
    }
  }

  statement {
    sid    = "LambdaECRImageRetrievalPolicy"
    effect = "Allow"

    actions = [
      "ecr:BatchGetImage",
      "ecr:GetDownloadUrlForLayer",
      "ecr:SetRepositoryPolicy",
      "ecr:DeleteRepositoryPolicy",
      "ecr:GetRepositoryPolicy",
    ]

    principals {
      identifiers = [
        "lambda.amazonaws.com",
      ]

      type = "Service"
    }
  }
}
