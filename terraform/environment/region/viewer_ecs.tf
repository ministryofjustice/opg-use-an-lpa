//----------------------------------
// Viewer ECS Service level config

resource "aws_ecs_service" "viewer" {
  name             = "viewer-service"
  cluster          = aws_ecs_cluster.use_an_lpa.id
  task_definition  = aws_ecs_task_definition.viewer.arn
  desired_count    = var.autoscaling.view.minimum
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.viewer_ecs_service.id]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.viewer.arn
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
  }

  provider = aws.region
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "viewer_ecs_service" {
  name_prefix = "${var.environment_name}-viewer-ecs-service"
  description = "Use service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

// 80 in from the ELB
resource "aws_security_group_rule" "viewer_ecs_service_ingress" {
  description              = "Allow Port 80 ingress from the application load balancer"
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.viewer_ecs_service.id
  source_security_group_id = aws_security_group.viewer_loadbalancer.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

// Anything out
resource "aws_security_group_rule" "viewer_ecs_service_egress" {
  description       = "Allow any egress from Use service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-egress-sgr - open egress for ECR access
  security_group_id = aws_security_group.viewer_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_security_group_rule" "viewer_ecs_service_elasticache_ingress" {
  description              = "Allow elasticache ingress for Use service"
  type                     = "ingress"
  from_port                = 0
  to_port                  = 6379
  protocol                 = "tcp"
  security_group_id        = data.aws_security_group.brute_force_cache_service.id
  source_security_group_id = aws_security_group.viewer_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//--------------------------------------
// Viewer ECS Service Task level config

resource "aws_ecs_task_definition" "viewer" {
  family                   = "${var.environment_name}-viewer"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.viewer_web}, ${local.viewer_app} ${var.feature_flags.deploy_opentelemetry_sidecar ? ", ${local.viewer_aws_otel_collector}" : ""}]"
  task_role_arn            = var.ecs_task_roles.viewer_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn

  provider = aws.region
}

//----------------
// Permissions

resource "aws_iam_role_policy" "viewer_permissions_role" {
  name   = "${var.environment_name}-${local.policy_region_prefix}-ViewerApplicationPermissions"
  policy = data.aws_iam_policy_document.viewer_permissions_role.json
  role   = var.ecs_task_roles.viewer_task_role.id

  provider = aws.region
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "viewer_permissions_role" {
  statement {
    sid    = "${local.policy_region_prefix}XRayPermissions"
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
    sid    = "${local.policy_region_prefix}KMSPermissions"
    effect = "Allow"

    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]

    resources = [data.aws_kms_alias.sessions_viewer.target_key_arn]
  }

  provider = aws.region
}

//-----------------------------------------------
// Viewer ECS Service Task Container level config

locals {
  viewer_web = jsonencode(
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
          awslogs-group         = var.application_logs_name,
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.viewer-web.use-an-lpa"
        }
      },
      environment = [
        {
          name  = "WEB_DOMAIN",
          value = "https://${var.route_53_fqdns.public_view}"
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
          name  = "CONTAINER_VERSION",
          value = var.container_version
      }]
  })

  viewer_aws_otel_collector = jsonencode(
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
          awslogs-group         = var.application_logs_name,
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.viewer-otel.use-an-lpa"
        }
      },
      environment = []
  })

  viewer_app = jsonencode(
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
          awslogs-group         = var.application_logs_name,
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.viewer-app.use-an-lpa"
        }
      },
      environment = local.viewer_app_environment_variables
    }
  )


  viewer_app_environment_variables = concat(
    [
      {
        name  = "CONTEXT",
        value = "viewer"
      },
      {
        name  = "KMS_SESSION_CMK_ALIAS",
        value = data.aws_kms_alias.sessions_viewer.name
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
        name  = "PDF_SERVICE_URL",
        value = "http://${local.pdf_service_fqdn}:8000"
      },
      {
        name  = "SESSION_EXPIRES",
        value = tostring(var.session_expires_view)
      },
      {
        name  = "COOKIE_EXPIRES",
        value = tostring(var.cookie_expires_view)
      },
      {
        name  = "GOOGLE_ANALYTICS_ID",
        value = var.google_analytics_id_view
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
        name  = "INSTRUCTIONS_AND_PREFERENCES",
        value = tostring(var.feature_flags.instructions_and_preferences)
      },
      {
        name  = "ALLOW_GOV_ONE_LOGIN",
        value = tostring(var.feature_flags.allow_gov_one_login)
      }
    ],
  )
}
