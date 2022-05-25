//----------------------------------
// Api ECS Service level config

resource "aws_ecs_service" "api" {
  name                              = "api-service"
  cluster                           = aws_ecs_cluster.use-an-lpa.id
  task_definition                   = aws_ecs_task_definition.api.arn
  desired_count                     = local.environment.autoscaling.api.minimum
  platform_version                  = "1.4.0"
  health_check_grace_period_seconds = 0

  network_configuration {
    security_groups  = [aws_security_group.api_ecs_service.id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.api_ecs.arn
  }

  capacity_provider_strategy {
    capacity_provider = local.capacity_provider
    weight            = 100
  }

  wait_for_steady_state = true

  lifecycle {
    create_before_destroy = true
  }
}

//-----------------------------------------------
// Api service discovery

resource "aws_service_discovery_service" "api_ecs" {
  name = "api"

  dns_config {
    namespace_id = aws_service_discovery_private_dns_namespace.internal_ecs.id

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
  api_service_fqdn = "${aws_service_discovery_service.api_ecs.name}.${aws_service_discovery_private_dns_namespace.internal_ecs.name}"
}

//----------------------------------
// The Api service's Security Groups

resource "aws_security_group" "api_ecs_service" {
  name_prefix = "${local.environment_name}-api-ecs-service"
  description = "API service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }
}

//----------------------------------
// 80 in from Viewer ECS service

resource "aws_security_group_rule" "api_ecs_service_viewer_ingress" {
  description              = "Allow Port 80 ingress from the View service"
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.viewer_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }
}

//----------------------------------
// 80 in from Actor ECS service

resource "aws_security_group_rule" "api_ecs_service_actor_ingress" {
  description              = "Allow Port 80 ingress from the Use service"
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.api_ecs_service.id
  source_security_group_id = aws_security_group.actor_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "api_ecs_service_egress" {
  description       = "Allow any egress from API service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS007 - open egress for ECR access
  security_group_id = aws_security_group.api_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }
}

//--------------------------------------
// Api ECS Service Task level config

resource "aws_ecs_task_definition" "api" {
  family                   = "${local.environment_name}-api"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.api_web}, ${local.api_app} ${local.environment.deploy_opentelemetry_sidecar ? ", ${local.api_aws_otel_collector}" : ""}]"
  task_role_arn            = aws_iam_role.api_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
}

//----------------
// Permissions

resource "aws_iam_role" "api_task_role" {
  name               = "${local.environment_name}-api-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}

resource "aws_iam_role_policy" "api_permissions_role" {
  name   = "${local.environment_name}-apiApplicationPermissions"
  policy = data.aws_iam_policy_document.api_permissions_role.json
  role   = aws_iam_role.api_task_role.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "api_permissions_role" {
  statement {
    sid    = "xrayaccess"
    effect = "Allow"

    actions = [
      "xray:PutTraceSegments",
      "xray:PutTelemetryRecords",
      "xray:GetSamplingRules",
      "xray:GetSamplingTargets",
      "xray:GetSamplingStatisticSummaries",
    ]

    resources = ["*"]
  }

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
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/GET/use-an-lpa/*",
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/POST/use-an-lpa/lpas/requestCode"
    ]
  }

  statement {
    sid    = "lpacodesaccess"
    effect = "Allow"
    actions = [
      "execute-api:Invoke",
    ]
    resources = [
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/GET/healthcheck",
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/POST/revoke",
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/POST/validate",
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/POST/exists",
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
          awslogs-stream-prefix = "${local.environment_name}.api-web.use-an-lpa"
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

  api_aws_otel_collector = jsonencode(
    {
      cpu         = 0,
      essential   = true,
      image       = "public.ecr.aws/aws-observability/aws-otel-collector:v0.14.1",
      mountPoints = [],
      name        = "aws-otel-collector",
      command = [
        "--config=/etc/ecs/ecs-default-config.yaml"
      ],
      portMappings = [],
      volumesFrom  = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment_name}.actor-otel.use-an-lpa"
        }
      },
      environment = []
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
      healthCheck = {
        command     = ["CMD", "/usr/local/bin/health-check.sh"],
        startPeriod = 90,
        interval    = 10,
        timeout     = 30,
        retries     = 3
      },
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment_name}.api-app.use-an-lpa"
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
          value = local.environment.lpas_collection_endpoint
        },
        {
          name  = "LPA_CODES_API_ENDPOINT",
          value = local.environment.lpa_codes_endpoint
        },
        {
          name  = "USE_LEGACY_CODES_SERVICE",
          value = tostring(local.environment.application_flags.use_legacy_codes_service)
        },
        {
          name  = "LOGGING_LEVEL",
          value = tostring(local.environment.logging_level)
        },
        {
          name  = "ALLOW_OLDER_LPAS",
          value = tostring(local.environment.application_flags.allow_older_lpas)
        },
        {
          name  = "ALLOW_MERIS_LPAS",
          value = tostring(local.environment.application_flags.allow_meris_lpas)
        },
        {
          name  = "SAVE_OLDER_LPA_REQUESTS",
          value = tostring(local.environment.application_flags.save_older_lpa_requests)
        },
        {
          name  = "DONT_SEND_LPAS_REGISTERED_AFTER_SEP_2019_TO_CLEANSING_TEAM",
          value = tostring(local.environment.application_flags.dont_send_lpas_registered_after_sep_2019_to_cleansing_team)
        }
      ]
  })
}
