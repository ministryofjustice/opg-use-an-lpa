//----------------------------------
// Viewer ECS Service level config

resource "aws_ecs_service" "viewer" {
  name             = "viewer-service"
  cluster          = aws_ecs_cluster.use-an-lpa.id
  task_definition  = aws_ecs_task_definition.viewer.arn
  desired_count    = local.environment.autoscaling.view.minimum
  launch_type      = "FARGATE"
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.viewer_ecs_service.id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.viewer.arn
    container_name   = "web"
    container_port   = 80
  }

  wait_for_steady_state = true

  lifecycle {
    create_before_destroy = true
  }

  depends_on = [aws_lb.viewer]
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "viewer_ecs_service" {
  name_prefix = "${local.environment_name}-viewer-ecs-service"
  description = "Use service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }
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
}

// Anything out
resource "aws_security_group_rule" "viewer_ecs_service_egress" {
  description       = "Allow any egress from Use service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS007 - open egress for ECR access
  security_group_id = aws_security_group.viewer_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }
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
}

//--------------------------------------
// Viewer ECS Service Task level config

resource "aws_ecs_task_definition" "viewer" {
  family                   = "${local.environment_name}-viewer"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.viewer_web}, ${local.viewer_app}, ${local.viewer_aws_otel_collector}]"
  task_role_arn            = aws_iam_role.viewer_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
}

//----------------
// Permissions

resource "aws_iam_role" "viewer_task_role" {
  name               = "${local.environment_name}-viewer-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}

resource "aws_iam_role_policy" "viewer_permissions_role" {
  name   = "${local.environment_name}-ViewerApplicationPermissions"
  policy = data.aws_iam_policy_document.viewer_permissions_role.json
  role   = aws_iam_role.viewer_task_role.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "viewer_permissions_role" {
  statement {
    effect = "Allow"

    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]

    resources = [data.aws_kms_alias.sessions_viewer.target_key_arn]
  }
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
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment_name}.viewer-web.use-an-lpa"
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

  viewer_aws_otel_collector = jsonencode(
    {
      cpu         = 0,
      essential   = true,
      image       = "public.ecr.aws/aws-observability/aws-otel-collector:v0.14.1",
      mountPoints = [],
      name        = "aws-otel-collector",
      command = [
        "--config=/etc/ecs/ecs-cloudwatch-xray.yaml"
      ],
      portMappings = [],
      volumesFrom  = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment_name}.viewer-otel.use-an-lpa"
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
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment_name}.viewer-app.use-an-lpa"
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
        value = tostring(local.environment.session_expires_view)
      },
      {
        name  = "COOKIE_EXPIRES",
        value = tostring(local.environment.cookie_expires_view)
      },
      {
        name  = "GOOGLE_ANALYTICS_ID",
        value = local.environment.google_analytics_id_view
      },
      {
        name  = "LOGGING_LEVEL",
        value = tostring(local.environment.logging_level)
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
    ],
  )
}
