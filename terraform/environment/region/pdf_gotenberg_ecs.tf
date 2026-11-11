//----------------------------------
// Gotenberg ECS Service level config

resource "aws_ecs_service" "gotenberg" {
  name             = "gotenberg"
  cluster          = aws_ecs_cluster.use_an_lpa.id
  task_definition  = aws_ecs_task_definition.gotenberg.arn
  desired_count    = local.gotenberg_desired_count
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.pdf_ecs_service.id]
    subnets          = data.aws_subnet.application[*].id
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.gotenberg.arn
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

//-----------------------------------------------
// Gotenberg service discovery

resource "aws_service_discovery_service" "gotenberg" {
  name = "gotenberg"

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

resource "aws_ecs_task_definition" "gotenberg" {
  family                   = "${var.environment_name}-gotenberg"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.gotenberg}]"
  task_role_arn            = var.ecs_task_roles.gotenberg_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn

  provider = aws.region
}

//-------------------------------------------
// Gotenberg ECS Service Task Container level config

locals {
  gotenberg = jsonencode({
    cpu        = 1,
    essential  = true,
    image      = "${data.aws_ecr_repository.gotenberg.repository_url}@${data.aws_ecr_image.gotenberg.image_digest}",
    name       = "gotenberg",
    privileged = false,
    user       = "gotenberg",
    portMappings = [
      {
        containerPort = 8000,
        hostPort      = 8000,
        protocol      = "tcp"
      }
    ],
    healthCheck = {
      command = [
        "CMD-SHELL",
        "curl -f -s http://localhost:8000/health || exit 1"
      ],
      startPeriod = 30,
      interval    = 15,
      timeout     = 10,
      retries     = 3
    },
    logConfiguration = {
      logDriver = "awslogs",
      options = {
        awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
        awslogs-region        = var.region_name,
        awslogs-stream-prefix = "${var.environment_name}.gotenberg"
      }
    },
    environment = [
      {
        name  = "API_PORT",
        value = "8000"
      },
      {
        name  = "CHROMIUM_MAX_QUEUE_SIZE",
        value = "30"
      }
    ]
  })
}
