//----------------------------------
// Api ECS Service level config

resource "aws_ecs_service" "api" {
  name             = "api"
  cluster          = aws_ecs_cluster.use-an-lpa.id
  task_definition  = aws_ecs_task_definition.api.arn
  desired_count    = local.account.autoscaling.api.minimum
  launch_type      = "FARGATE"
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.api_ecs_service.id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.api.arn
  }
}

//-----------------------------------------------
// Api service discovery

resource "aws_service_discovery_service" "api" {
  name = "api"

  dns_config {
    namespace_id = aws_service_discovery_private_dns_namespace.internal.id

    dns_records {
      ttl  = 10
      type = "A"
    }

    routing_policy = "MULTIVALUE"
  }

  health_check_custom_config {
    failure_threshold = 1
  }
}

//
locals {
  api_service_fqdn = "${aws_service_discovery_service.api.name}.${aws_service_discovery_private_dns_namespace.internal.name}"
}

//----------------------------------
// The Api service's Security Groups

resource "aws_security_group" "api_ecs_service" {
  name_prefix = "${local.environment}-api-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

//----------------------------------
// 80 in from Viewer ECS service

resource "aws_security_group_rule" "api_ecs_service_viewer_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.viewer_ecs_service.id
}

//----------------------------------
// 80 in from Actor ECS service

resource "aws_security_group_rule" "api_ecs_service_actor_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.actor_ecs_service.id
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "api_ecs_service_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.api_ecs_service.id
}

//--------------------------------------
// Api ECS Service Task level config

resource "aws_ecs_task_definition" "api" {
  family                   = "${local.environment}-api"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.api_web}, ${local.api_app}]"
  task_role_arn            = aws_iam_role.api_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}

//----------------
// Permissions

resource "aws_iam_role" "api_task_role" {
  name               = "${local.environment}-api-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
  tags               = local.default_tags
}

resource "aws_iam_role_policy" "api_permissions_role" {
  name   = "${local.environment}-apiApplicationPermissions"
  policy = data.aws_iam_policy_document.api_permissions_role.json
  role   = aws_iam_role.api_task_role.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "api_permissions_role" {
  statement {
    effect = "Allow"

    actions = [
      "dynamodb:*",
    ]

    resources = [
      aws_dynamodb_table.actor_codes_table.arn,
      "${aws_dynamodb_table.actor_codes_table.arn}/index/*",
      aws_dynamodb_table.actor_users_table.arn,
      "${aws_dynamodb_table.actor_users_table.arn}/index/*",
      aws_dynamodb_table.viewer_codes_table.arn,
      "${aws_dynamodb_table.viewer_codes_table.arn}/index/*",
      aws_dynamodb_table.viewer_activity_table.arn,
      "${aws_dynamodb_table.viewer_activity_table.arn}/index/*",
      aws_dynamodb_table.user_lpa_actor_map.arn,
      "${aws_dynamodb_table.user_lpa_actor_map.arn}/index/*",
    ]
  }

  statement {
    sid    = "lpacollectionsaccess"
    effect = "Allow"

    actions = [
      "execute-api:Invoke",
    ]

    resources = [
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/GET/use-an-lpa/*",
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/POST/use-an-lpa/lpas/requestCode"
    ]
  }

  statement {
    sid    = "lpacodesaccess"
    effect = "Allow"
    actions = [
      "execute-api:Invoke",
    ]
    resources = [
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/GET/healthcheck",
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/POST/revoke",
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/POST/validate",
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/POST/exists",
    ]
  }
}

//-----------------------------------------------
// API ECS Service Task Container level config

locals {
  api_web = jsonencode(
    {
      cpu         = 1,
      essential   = true,
      image       = "${data.aws_ecr_repository.use_an_lpa_api_web.repository_url}:${var.container_version}",
      mountPoints = [],
      name        = "web",
      portMappings = [
        {
          containerPort = 80,
          hostPort      = 80,
          protocol      = "tcp"
        }
      ],
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment}.api-web.use-an-lpa"
        }
      },
      environment = [
        {
          name  = "APP_HOST",
          value = "127.0.0.1"
        },
        {
          name  = "APP_PORT",
          value = "9000"
        },
        {
          name  = "TIMEOUT",
          value = "60"
        },
        {
          name  = "CONTAINER_VERSION",
          value = var.container_version
      }]
  })


  api_app = jsonencode(
    {
      cpu         = 1,
      essential   = true,
      image       = "${data.aws_ecr_repository.use_an_lpa_api_app.repository_url}:${var.container_version}",
      mountPoints = [],
      name        = "app",
      portMappings = [
        {
          containerPort = 9000,
          hostPort      = 9000,
          protocol      = "tcp"
        }
      ],
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment}.api-app.use-an-lpa"
        }
      },
      secrets = [
        {
          name      = "NOTIFY_API_KEY",
          valueFrom = data.aws_secretsmanager_secret.notify_api_key.arn
      }],
      environment = [
        {
          name  = "DYNAMODB_TABLE_ACTOR_CODES",
          value = aws_dynamodb_table.actor_codes_table.name
        },
        {
          name  = "DYNAMODB_TABLE_ACTOR_USERS",
          value = aws_dynamodb_table.actor_users_table.name
        },
        {
          name  = "DYNAMODB_TABLE_VIEWER_CODES",
          value = aws_dynamodb_table.viewer_codes_table.name
        },
        {
          name  = "DYNAMODB_TABLE_VIEWER_ACTIVITY",
          value = aws_dynamodb_table.viewer_activity_table.name
        },
        {
          name  = "DYNAMODB_TABLE_USER_LPA_ACTOR_MAP",
          value = aws_dynamodb_table.user_lpa_actor_map.name
        },
        {
          name  = "CONTAINER_VERSION",
          value = var.container_version
        },
        {
          name  = "SIRIUS_API_ENDPOINT",
          value = local.account.lpas_collection_endpoint
        },
        {
          name  = "LPA_CODES_API_ENDPOINT",
          value = local.account.lpa_codes_endpoint
        },
        {
          name  = "USE_LEGACY_CODES_SERVICE",
          value = tostring(local.account.use_legacy_codes_service)
        },
        {
          name  = "LOGGING_LEVEL",
          value = tostring(local.account.logging_level)
      }]
  })
}

output "api_web_deployed_version" {
  value = "${data.aws_ecr_repository.use_an_lpa_api_web.repository_url}:${var.container_version}"
}

output "api_app_deployed_version" {
  value = "${data.aws_ecr_repository.use_an_lpa_api_app.repository_url}:${var.container_version}"
}
