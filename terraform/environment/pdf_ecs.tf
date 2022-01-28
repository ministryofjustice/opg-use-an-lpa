//----------------------------------
// pdf ECS Service level config

resource "aws_ecs_service" "pdf" {
  name             = "pdf-service"
  cluster          = aws_ecs_cluster.use-an-lpa.id
  task_definition  = aws_ecs_task_definition.pdf.arn
  desired_count    = local.environment.autoscaling.pdf.minimum
  launch_type      = "FARGATE"
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.pdf_ecs_service.id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  service_registries {
    registry_arn = aws_service_discovery_service.pdf.arn
  }

  wait_for_steady_state = true

  lifecycle {
    create_before_destroy = true
  }
}

//-----------------------------------------------
// pdf service discovery

resource "aws_service_discovery_service" "pdf" {
  name = "pdf"

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
  pdf_service_fqdn = "${aws_service_discovery_service.pdf.name}.${aws_service_discovery_private_dns_namespace.internal.name}"
}

//----------------------------------
// The pdf service's Security Groups

resource "aws_security_group" "pdf_ecs_service" {
  name_prefix = "${local.environment_name}-pdf-ecs-service"
  description = "PDF generator service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }
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
}

//--------------------------------------
// pdf ECS Service Task level config

resource "aws_ecs_task_definition" "pdf" {
  family                   = "${local.environment_name}-pdf"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.pdf_app}]"
  task_role_arn            = aws_iam_role.pdf_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
}

//----------------
// Permissions

resource "aws_iam_role" "pdf_task_role" {
  name               = "${local.environment_name}-pdf-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
}

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
        awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
        awslogs-region        = "eu-west-1",
        awslogs-stream-prefix = "${local.environment_name}.pdf-app.use-an-lpa"
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
