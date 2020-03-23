//----------------------------------
// The Code creation service's Security Groups

resource "aws_security_group" "code_creation_ecs_service" {
  name_prefix = "${local.environment}-code-creation-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "code_creation_ecs_service_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.code_creation_ecs_service.id
}

//--------------------------------------
// Code creation ECS Service Task level config

resource "aws_ecs_task_definition" "code_creation" {
  family                   = "${local.environment}-code-creation"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.code_creation_app}]"
  task_role_arn            = aws_iam_role.code_creation_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}

//----------------
// Permissions

resource "aws_iam_role" "code_creation_task_role" {
  name               = "${local.environment}-code-creation-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
  tags               = local.default_tags
}

resource "aws_iam_role_policy" "code_creation_permissions_role" {
  name   = "${local.environment}-codeCreationApplicationPermissions"
  policy = data.aws_iam_policy_document.code_creation_permissions_role.json
  role   = aws_iam_role.code_creation_task_role.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "code_creation_permissions_role" {
  statement {
    effect = "Allow"

    actions = [
      "dynamodb:*",
    ]

    resources = [
      aws_dynamodb_table.actor_codes_table.arn,
      "${aws_dynamodb_table.actor_codes_table.arn}/index/*"
    ]
  }

  statement {
    effect = "Allow"

    actions = [
      "execute-api:Invoke",
    ]

    resources = ["arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/GET/use-an-lpa/*"]
  }
}

//-----------------------------------------------
// Code Creation ECS Service Task Container level config

locals {
  code_creation_app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.use_an_lpa_api_app.repository_url}:${var.container_version}",
    "command": [ "php console.php" ],
    "mountPoints": [],
    "name": "app",
    "volumesFrom": [],
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
            "awslogs-group": "${data.aws_cloudwatch_log_group.use-an-lpa.name}",
            "awslogs-region": "eu-west-1",
            "awslogs-stream-prefix": "code-creation-app.use-an-lpa"
        }
    },
    "environment": [
    {
      "name": "DYNAMODB_TABLE_ACTOR_CODES",
      "value": "${aws_dynamodb_table.actor_codes_table.name}"
    },
    {
      "name": "DYNAMODB_TABLE_ACTOR_USERS",
      "value": "${aws_dynamodb_table.actor_users_table.name}"
    },
    {
      "name": "DYNAMODB_TABLE_VIEWER_CODES",
      "value": "${aws_dynamodb_table.viewer_codes_table.name}"
    },
    {
      "name": "DYNAMODB_TABLE_VIEWER_ACTIVITY",
      "value": "${aws_dynamodb_table.viewer_activity_table.name}"
    },
    {
      "name": "DYNAMODB_TABLE_USER_LPA_ACTOR_MAP",
      "value": "${aws_dynamodb_table.user_lpa_actor_map.name}"
    },
    {
      "name": "CONTAINER_VERSION",
      "value": "${var.container_version}"
    },
    {
      "name": "SIRIUS_API_ENDPOINT",
      "value": "${local.account.api_gateway_endpoint}"
    },
    {
      "name": "LOGGING_LEVEL",
      "value": "${local.account.logging_level}"
    }]
  }

EOF

}

output "code_creation_app_deployed_version" {
  value = "${data.aws_ecr_repository.use_an_lpa_api_app.repository_url}:${var.container_version}"
}
