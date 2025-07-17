//----------------------------------
// Actor ECS Service level config

resource "aws_ecs_service" "use" {
  name             = "actor-service"
  cluster          = aws_ecs_cluster.use_an_lpa.id
  task_definition  = aws_ecs_task_definition.use.arn
  desired_count    = local.use_desired_count
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.use_ecs_service.id]
    subnets          = data.aws_default_tags.current.tags.account-name == "development" ? data.aws_subnet.application[*].id : data.aws_subnets.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.use.arn
    container_name   = "web"
    container_port   = 80
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
    ignore_changes = [
      desired_count
    ]
  }

  timeouts {
    create = "7m"
    update = "4m"
  }

  provider = aws.region
}

moved {
  from = aws_ecs_service.actor
  to   = aws_ecs_service.use
}
//----------------------------------
// The service's Security Groups

resource "aws_security_group" "use_ecs_service" {
  name_prefix = "${var.environment_name}-actor-ecs-service"
  description = "Use service security group"
  vpc_id      = data.aws_default_tags.current.tags.account-name == "development" ? data.aws_vpc.main.id : data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group.actor_ecs_service
  to   = aws_security_group.use_ecs_service
}

// 80 in from the ELB
resource "aws_security_group_rule" "use_ecs_service_ingress" {
  description              = "Allow Port 80 ingress from the application load balancer"
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.use_ecs_service.id
  source_security_group_id = aws_security_group.use_loadbalancer.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group_rule.actor_ecs_service_ingress
  to   = aws_security_group_rule.use_ecs_service_ingress
}

// Anything out
resource "aws_security_group_rule" "use_ecs_service_egress" {
  description       = "Allow any egress from Use service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-egress-sgr - open egress for ECR access
  security_group_id = aws_security_group.use_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group_rule.actor_ecs_service_egress
  to   = aws_security_group_rule.use_ecs_service_egress
}

resource "aws_security_group_rule" "use_ecs_service_elasticache_ingress" {
  description              = "Allow elasticache ingress for Use service"
  type                     = "ingress"
  from_port                = 0
  to_port                  = 6379
  protocol                 = "tcp"
  security_group_id        = data.aws_security_group.brute_force_cache_service.id
  source_security_group_id = aws_security_group.use_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group_rule.actor_ecs_service_elasticache_ingress
  to   = aws_security_group_rule.use_ecs_service_elasticache_ingress
}

//--------------------------------------
// Actor ECS Service Task level config

resource "aws_ecs_task_definition" "use" {
  family                   = "${var.environment_name}-actor"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 256
  memory                   = 512
  container_definitions    = "[${local.actor_web}, ${local.actor_app} ${var.feature_flags.deploy_opentelemetry_sidecar ? ", ${local.actor_aws_otel_collector}" : ""}]"
  task_role_arn            = var.ecs_task_roles.use_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn

  provider = aws.region
}

//----------------
// Permissions

resource "aws_iam_role_policy" "use_permissions_role" {
  name   = "${var.environment_name}-${local.policy_region_prefix}-ActorApplicationPermissions"
  policy = data.aws_iam_policy_document.use_permissions_role.json
  role   = var.ecs_task_roles.use_task_role.id

  provider = aws.region
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "use_permissions_role" {
  statement {
    sid    = "${local.policy_region_prefix}XRayAccess"
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
    sid    = "${local.policy_region_prefix}KMSAccess"
    effect = "Allow"

    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]

    resources = [data.aws_kms_alias.sessions_actor.target_key_arn]
  }

  provider = aws.region
}

//-----------------------------------------------
// Actor ECS Service Task Container level config

locals {
  actor_web = jsonencode(
    {
      cpu         = 1,
      essential   = true,
      image       = "${data.aws_ecr_repository.use_an_lpa_front_web.repository_url}:${var.container_version}",
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
          awslogs-stream-prefix = "${var.environment_name}.actor-web.use-an-lpa"
        }
      },
      environment = [
        {
          name  = "WEB_DOMAIN",
          value = "https://${local.route53_fqdns.public_facing_use}"
        },
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

  actor_aws_otel_collector = jsonencode(
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

  actor_app = jsonencode(
    {
      cpu         = 1,
      essential   = true,
      image       = "${data.aws_ecr_repository.use_an_lpa_front_app.repository_url}:${var.container_version}",
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
          awslogs-stream-prefix = "${var.environment_name}.actor-app.use-an-lpa"
        }
      },
      secrets = [
        {
          name      = "NOTIFY_API_KEY",
          valueFrom = data.aws_secretsmanager_secret.notify_api_key.arn
      }],
      environment = [
        {
          name  = "CONTEXT",
          value = "actor"
        },
        {
          name  = "KMS_SESSION_CMK_ALIAS",
          value = data.aws_kms_alias.sessions_actor.name
        },
        {
          name  = "CONTAINER_VERSION",
          value = var.container_version
        },
        {
          name  = "API_SERVICE_URL",
          value = "http://${local.api_service_fqdn}"
        },
        {
          name  = "SESSION_EXPIRES",
          value = tostring(var.session_expires_use)
        },
        {
          name  = "SESSION_EXPIRY_WARNING",
          value = tostring(var.session_expiry_warning)
        },
        {
          name  = "COOKIE_EXPIRES",
          value = tostring(var.cookie_expires_use)
        },
        {
          name  = "GOOGLE_ANALYTICS_ID",
          value = var.google_analytics_id_use
        },
        {
          name  = "LOGGING_LEVEL",
          value = tostring(var.logging_level)
        },
        {
          name  = "BRUTE_FORCE_CACHE_URL",
          value = "tls://${data.aws_elasticache_replication_group.brute_force_cache_replication_group.primary_endpoint_address}"
        },
        {
          name  = "BRUTE_FORCE_CACHE_PORT",
          value = tostring(data.aws_elasticache_replication_group.brute_force_cache_replication_group.port)
        },
        {
          name  = "BRUTE_FORCE_CACHE_TIMEOUT",
          value = "60"
        },
        {
          name  = "DELETE_LPA_FEATURE",
          value = tostring(var.feature_flags.delete_lpa_feature)
        },
        {
          name  = "ALLOW_MERIS_LPAS",
          value = tostring(var.feature_flags.allow_meris_lpas)
        },
        {
          name  = "SUPPORT_DATASTORE_LPAS",
          value = tostring(var.feature_flags.support_datastore_lpas)
        },
        {
          name  = "PAPER_VERIFICATION",
          value = tostring(var.feature_flags.paper_verification)
        }
      ]
  })
}
