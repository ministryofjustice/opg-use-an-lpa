//----------------------------------
// pdf ECS Service level config

resource "aws_ecs_service" "pdf" {
  name             = "pdf-service"
  cluster          = aws_ecs_cluster.use_an_lpa.id
  task_definition  = aws_ecs_task_definition.pdf.arn
  desired_count    = var.autoscaling.pdf.minimum
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.pdf_ecs_service.id]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.pdf_ecs.arn
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
// pdf service discovery

resource "aws_service_discovery_service" "pdf_ecs" {
  name = "pdf"

  dns_config {
    namespace_id = var.aws_service_discovery_service.id

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
  pdf_service_fqdn = "${aws_service_discovery_service.pdf_ecs.name}.${var.aws_service_discovery_service.name}"
}

//----------------------------------
// The pdf service's Security Groups

resource "aws_security_group" "pdf_ecs_service" {
  name_prefix = "${var.environment_name}-pdf-ecs-service"
  description = "PDF generator service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//----------------------------------
// 80 in from Viewer ECS service

resource "aws_security_group_rule" "pdf_ecs_service_viewer_ingress" {
  description              = "Allow Port 80 ingress from the View service"
  type                     = "ingress"
  from_port                = 80
  to_port                  = 8000
  protocol                 = "tcp"
  security_group_id        = aws_security_group.pdf_ecs_service.id
  source_security_group_id = aws_security_group.viewer_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "pdf_ecs_service_egress" {
  description       = "Allow any egress from Use service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS007 - open egress for ECR access
  security_group_id = aws_security_group.pdf_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//--------------------------------------
// pdf ECS Service Task level config

resource "aws_ecs_task_definition" "pdf" {
  family                   = "${var.environment_name}-pdf"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.pdf_app}]"
  task_role_arn            = var.ecs_task_roles.pdf_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn

  provider = aws.region
}

//----------------
// Permissions

//-----------------------------------------------
// pdf ECS Service Task Container level config
locals {
  pdf_app = jsonencode({
    cpu         = 1,
    essential   = true,
    image       = "${data.aws_ecr_repository.use_an_lpa_pdf.repository_url}@${data.aws_ecr_image.pdf_service.image_digest}",
    mountPoints = [],
    name        = "pdf",
    portMappings = [
      {
        containerPort = 8000,
        hostPort      = 8000,
        protocol      = "tcp"
    }],
    volumesFrom = [],
    logConfiguration = {
      logDriver = "awslogs",
      options = {
        awslogs-group         = var.application_logs_name,
        awslogs-region        = data.aws_region.current.name,
        awslogs-stream-prefix = "${var.environment_name}.pdf-app.use-an-lpa"
      }
    },
    environment = [
      {
        name  = "PDF_SERVICE_PORT",
        value = "8000"
      }
    ]
  })
}
