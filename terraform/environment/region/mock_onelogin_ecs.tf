//----------------------------------
// Mock One Login ECS Service level config

resource "aws_ecs_service" "mock_onelogin" {
  count                             = var.mock_onelogin_enabled ? 1 : 0
  name                              = "mock-onelogin-service"
  cluster                           = aws_ecs_cluster.use_an_lpa.id
  task_definition                   = aws_ecs_task_definition.mock_onelogin[0].arn
  desired_count                     = local.mock_onelogin_desired_count
  platform_version                  = "1.4.0"
  health_check_grace_period_seconds = 0

  network_configuration {
    security_groups  = [aws_security_group.mock_onelogin_ecs_service[0].id]
    subnets          = data.aws_default_tags.current.tags.account-name != "production" ? data.aws_subnet.application[*].id : data.aws_subnets.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.mock_onelogin[0].arn
    container_name   = "mock_onelogin"
    container_port   = 8080
  }

  service_registries {
    registry_arn = aws_service_discovery_service.mock_onelogin_ecs[0].arn
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
// Mock One Login service discovery

resource "aws_service_discovery_service" "mock_onelogin_ecs" {
  count = var.mock_onelogin_enabled ? 1 : 0
  name  = "mock-onelogin"

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
  mock_onelogin_service_fqdn = var.mock_onelogin_enabled ? "${aws_service_discovery_service.mock_onelogin_ecs[0].name}.${aws_service_discovery_private_dns_namespace.internal_ecs.name}" : ""
}

//----------------------------------
// The Mock One Login service's Security Groups

resource "aws_security_group" "mock_onelogin_ecs_service" {
  count       = var.mock_onelogin_enabled ? 1 : 0
  name_prefix = "${var.environment_name}-mock-onelogin-ecs-service"
  description = "Mock One Login service security group"
  vpc_id      = data.aws_default_tags.current.tags.account-name != "production" ? data.aws_vpc.main.id : data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//----------------------------------
// 8080 in from the ELB
resource "aws_security_group_rule" "mock_onelogin_ecs_service_ingress" {
  count                    = var.mock_onelogin_enabled ? 1 : 0
  description              = "Allow Port 8080 ingress from the application load balancer"
  type                     = "ingress"
  from_port                = 8080
  to_port                  = 8080
  protocol                 = "tcp"
  security_group_id        = aws_security_group.mock_onelogin_ecs_service[0].id
  source_security_group_id = aws_security_group.mock_onelogin_loadbalancer[0].id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//----------------------------------
// 80 in from API ECS service
resource "aws_security_group_rule" "mock_onelogin_ecs_service_api_ingress" {
  count                    = var.mock_onelogin_enabled ? 1 : 0
  description              = "Allow Port 8080 ingress from the Api service"
  type                     = "ingress"
  from_port                = 0
  to_port                  = 8080
  protocol                 = "tcp"
  security_group_id        = aws_security_group.mock_onelogin_ecs_service[0].id
  source_security_group_id = aws_security_group.api_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//----------------------------------
// Anything out
resource "aws_security_group_rule" "mock_onelogin_ecs_service_egress" {
  count             = var.mock_onelogin_enabled ? 1 : 0
  description       = "Allow any egress from Mock One Login service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-egress-sgr - open egress for ECR access
  security_group_id = aws_security_group.mock_onelogin_ecs_service[0].id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

//--------------------------------------
// Api ECS Service Task level config

resource "aws_ecs_task_definition" "mock_onelogin" {
  count                    = var.mock_onelogin_enabled ? 1 : 0
  family                   = "${var.environment_name}-mock-onelogin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.mock_onelogin_app}]"
  task_role_arn            = var.ecs_task_roles.mock_onelogin_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn

  provider = aws.region
}

//----------------
// Permissions

resource "aws_iam_role_policy" "mock_onelogin_permissions_role" {
  count  = var.mock_onelogin_enabled ? 1 : 0
  name   = "${var.environment_name}-${local.policy_region_prefix}-mockOneLoginApplicationPermissions"
  policy = data.aws_iam_policy_document.mock_onelogin_permissions_role[0].json
  role   = var.ecs_task_roles.mock_onelogin_task_role.id

  provider = aws.region
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "mock_onelogin_permissions_role" {
  count = var.mock_onelogin_enabled ? 1 : 0
  statement {
    sid    = "${local.policy_region_prefix}AllowSecretsManagerAccess"
    effect = "Allow"
    actions = [
      "secretsmanager:GetSecretValue",
    ]
    resources = [
      data.aws_secretsmanager_secret.gov_uk_onelogin_client_id.arn,
    ]
  }

  provider = aws.region
}

//-----------------------------------------------
// API ECS Service Task Container level config

locals {
  mock_onelogin_app = jsonencode(
    {
      cpu                    = 1,
      essential              = true,
      image                  = "${var.mock_onelogin_service_repository_url}:${var.mock_onelogin_service_container_version}",
      mountPoints            = [],
      readonlyRootFilesystem = true
      name                   = "mock_onelogin",
      portMappings = [
        {
          containerPort = 8080,
          hostPort      = 8080,
          protocol      = "tcp"
        }
      ],
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = data.aws_region.current.name,
          awslogs-stream-prefix = "${var.environment_name}.mock-onelogin-app.use-an-lpa"
        }
      },
      secrets = [
        {
          name      = "CLIENT_ID"
          valueFrom = data.aws_secretsmanager_secret.gov_uk_onelogin_client_id.arn
        }
      ],
      environment = [
        {
          name  = "PUBLIC_URL",
          value = "https://${local.route53_fqdns.mock_onelogin}"
        },
        {
          name  = "INTERNAL_URL",
          value = "http://${local.mock_onelogin_service_fqdn}:8080"
        },
        {
          name  = "REDIRECT_URL",
          value = "https://${local.route53_fqdns.public_facing_use}/home/login"
        },
        {
          name  = "TEMPLATE_SUB",
          value = "1"
        }
      ]
  })
}
