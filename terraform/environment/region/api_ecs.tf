//----------------------------------
// Api ECS Service level config

resource "aws_ecs_service" "api" {
  name                              = "api-service"
  cluster                           = aws_ecs_cluster.use_an_lpa.id
  task_definition                   = aws_ecs_task_definition.api.arn
  desired_count                     = local.api_desired_count
  platform_version                  = "1.4.0"
  health_check_grace_period_seconds = 0

  network_configuration {
    security_groups  = [aws_security_group.api_ecs_service.id]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.api_ecs.arn
  }

  capacity_provider_strategy {
    capacity_provider = var.capacity_provider
    weight            = 100
  }

  deployment_circuit_breaker {
    enable   = false
    rollback = false
  }

  deployment_controller {
    type = "ECS"
  }

  wait_for_steady_state = true

  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
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

  provider = aws.region
}

//
locals {
  api_service_fqdn = "${aws_service_discovery_service.api_ecs.name}.${aws_service_discovery_private_dns_namespace.internal_ecs.name}"
}

//----------------------------------
// The Api service's Security Groups

resource "aws_security_group" "api_ecs_service" {
  name_prefix = "${var.environment_name}-api-ecs-service"
  description = "API service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
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

  provider = aws.region
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

  provider = aws.region
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "api_ecs_service_egress" {
  description       = "Allow any egress from API service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-egress-sgr - open egress for ECR access
  security_group_id = aws_security_group.api_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//----------------------------------
// Allow API to access Elasticache
resource "aws_security_group_rule" "api_ecs_service_elasticache_ingress" {
  description              = "Allow elasticache ingress for API service"
  type                     = "ingress"
  from_port                = 0
  to_port                  = 6379
  protocol                 = "tcp"
  security_group_id        = data.aws_security_group.brute_force_cache_service.id
  source_security_group_id = aws_security_group.api_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//--------------------------------------
// Api ECS Service Task level config

resource "aws_ecs_task_definition" "api" {
  family                   = "${var.environment_name}-api"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.fpm_stats_export}, ${local.api_web}, ${local.api_app} ${var.feature_flags.deploy_opentelemetry_sidecar ? ", ${local.api_aws_otel_collector}" : ""}]"
  task_role_arn            = var.ecs_task_roles.api_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn

  provider = aws.region
}


//----------------
// Permissions

resource "aws_iam_role_policy" "api_permissions_role" {
  name   = "${var.environment_name}-${local.policy_region_prefix}-apiApplicationPermissions"
  policy = data.aws_iam_policy_document.api_permissions_role.json
  role   = var.ecs_task_roles.api_task_role.id

  provider = aws.region
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "api_permissions_role" {
  statement {
    sid    = "${local.policy_region_prefix}XrayAccess"
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
    sid    = "${local.policy_region_prefix}DynamoDbAccess"
    effect = "Allow"

    actions = [
      "dynamodb:*",
    ]

    resources = [
      local.dynamodb_tables_arns.actor_codes_table_arn,
      "${local.dynamodb_tables_arns.actor_codes_table_arn}/index/*",
      local.dynamodb_tables_arns.actor_users_table_arn,
      "${local.dynamodb_tables_arns.actor_users_table_arn}/index/*",
      local.dynamodb_tables_arns.viewer_codes_table_arn,
      "${local.dynamodb_tables_arns.viewer_codes_table_arn}/index/*",
      local.dynamodb_tables_arns.viewer_activity_table_arn,
      "${local.dynamodb_tables_arns.viewer_activity_table_arn}/index/*",
      local.dynamodb_tables_arns.user_lpa_actor_map_arn,
      "${local.dynamodb_tables_arns.user_lpa_actor_map_arn}/index/*",
      local.dynamodb_tables_arns.stats_table_arn,
      "${local.dynamodb_tables_arns.stats_table_arn}/index/*",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}LpaCollectionAccess"
    effect = "Allow"

    actions = [
      "execute-api:Invoke",
    ]

    resources = [
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/GET/use-an-lpa/*",
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/POST/use-an-lpa/lpas/requestCode"
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}LpaCodesAccess"
    effect = "Allow"
    actions = [
      "execute-api:Invoke",
    ]
    resources = [
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/GET/healthcheck",
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/POST/revoke",
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/POST/validate",
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/POST/exists",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}AllowSSMParameterAccess"
    effect = "Allow"
    actions = [
      "ssm:GetParameter",
    ]
    resources = var.parameter_store_arns
  }

  statement {
    sid    = "${local.policy_region_prefix}IapImagesAccess"
    effect = "Allow"
    actions = [
      "execute-api:Invoke",
    ]
    resources = [
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/GET/image-request/*",
      "arn:aws:execute-api:${data.aws_region.current.name}:${var.sirius_account_id}:*/*/GET/healthcheck",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}CloudWatchMetricsAccess"
    effect = "Allow"
    actions = [
      "cloudwatch:PutMetricData",
    ]
    resources = ["*"]
  }

  provider = aws.region
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
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.api-web.use-an-lpa"
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
          name  = "OPG_PHP_POOL_CHILDREN_MAX",
          value = "25"
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
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.actor-otel.use-an-lpa"
        }
      },
      environment = []
  })

  fpm_stats_export = jsonencode(
    {
      cpu       = 0,
      essential = false,
      image     = "ntse/export-php-metrics:v0.6.0",
      name      = "fpm-stats-export",
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.fpm-stats-export.use-an-lpa"
        }
      },
      dependsOn = [{
        containerName = "app"
        condition     = "HEALTHY"
      }]
    }
  )

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
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.api-app.use-an-lpa"
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
          value = var.dynamodb_tables.actor_codes_table.name
        },
        {
          name  = "DYNAMODB_TABLE_ACTOR_USERS",
          value = var.dynamodb_tables.actor_users_table.name
        },
        {
          name  = "DYNAMODB_TABLE_VIEWER_CODES",
          value = var.dynamodb_tables.viewer_codes_table.name
        },
        {
          name  = "DYNAMODB_TABLE_VIEWER_ACTIVITY",
          value = var.dynamodb_tables.viewer_activity_table.name
        },
        {
          name  = "DYNAMODB_TABLE_USER_LPA_ACTOR_MAP",
          value = var.dynamodb_tables.user_lpa_actor_map.name
        },
        {
          name  = "DYNAMODB_TABLE_STATS",
          value = var.dynamodb_tables.stats_table.name
        },
        {
          name  = "CONTAINER_VERSION",
          value = var.container_version
        },
        {
          name  = "SIRIUS_API_ENDPOINT",
          value = var.lpas_collection_endpoint
        },
        {
          name  = "LPA_CODES_API_ENDPOINT",
          value = var.lpa_codes_endpoint
        },
        {
          name  = "IAP_IMAGES_API_ENDPOINT",
          value = var.iap_images_endpoint
        },
        {
          name  = "LOGGING_LEVEL",
          value = tostring(var.logging_level)
        },
        {
          name  = "ALLOW_MERIS_LPAS",
          value = tostring(var.feature_flags.allow_meris_lpas)
        },
        {
          name  = "DONT_SEND_LPAS_REGISTERED_AFTER_SEP_2019_TO_CLEANSING_TEAM",
          value = tostring(var.feature_flags.dont_send_lpas_registered_after_sep_2019_to_cleansing_team)
        },
        {
          name  = "INSTRUCTIONS_AND_PREFERENCES",
          value = tostring(var.feature_flags.instructions_and_preferences)
        },
        {
          name  = "ALLOW_GOV_ONE_LOGIN",
          value = tostring(var.feature_flags.allow_gov_one_login)
        },
        {
          name  = "LOGIN_SERIAL_CACHE_URL",
          value = "tls://${data.aws_elasticache_replication_group.brute_force_cache_replication_group.primary_endpoint_address}"
        },
        {
          name  = "LOGIN_SERIAL_CACHE_PORT",
          value = tostring(data.aws_elasticache_replication_group.brute_force_cache_replication_group.port)
        },
        {
          name  = "LOGIN_SERIAL_CACHE_TIMEOUT",
          value = "60"
        }
      ]
  })
}
