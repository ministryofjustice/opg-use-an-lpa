//----------------------------------
// admin ECS Service level config

resource "aws_ecs_service" "admin" {
  name             = "admin-service"
  cluster          = aws_ecs_cluster.use-an-lpa.id
  task_definition  = aws_ecs_task_definition.admin.arn
  desired_count    = 1
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.admin_ecs_service.id]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = var.alb_tg_arns.admin.arn
    container_name   = "app"
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


moved {
  from = aws_ecs_service.admin[0]
  to   = aws_ecs_service.admin
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "admin_ecs_service" {
  name_prefix = "${var.environment_name}-admin-ecs-service"
  description = "Admin service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group.admin_ecs_service[0]
  to   = aws_security_group.admin_ecs_service
}

// 80 in from the ELB
resource "aws_security_group_rule" "admin_ecs_service_ingress" {
  description              = "Allow Port 80 ingress from the applciation load balancer"
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.admin_ecs_service.id
  source_security_group_id = var.admin_loadbalancer_security_group_id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group_rule.admin_ecs_service_ingress[0]
  to   = aws_security_group_rule.admin_ecs_service_ingress
}

// Anything out
resource "aws_security_group_rule" "admin_ecs_service_egress" {
  description       = "Allow any egress from Use service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS007 - open egress for ECR access
  security_group_id = aws_security_group.admin_ecs_service.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

moved {
  from = aws_security_group_rule.admin_ecs_service_egress[0]
  to   = aws_security_group_rule.admin_ecs_service_egress
}

//--------------------------------------
// admin ECS Service Task level config

resource "aws_ecs_task_definition" "admin" {
  family                   = "${var.environment_name}-admin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.admin_app}]"
  task_role_arn            = var.ecs_task_roles.admin_task_role.arn
  execution_role_arn       = var.ecs_execution_role.arn

  provider = aws.region
}

moved {
  from = aws_ecs_task_definition.admin[0]
  to   = aws_ecs_task_definition.admin
}

resource "aws_iam_role_policy" "admin_permissions_role" {
  name   = "${var.environment_name}-${local.policy_region_prefix}-adminApplicationPermissions"
  policy = data.aws_iam_policy_document.admin_permissions_role.json
  role   = var.ecs_task_roles.admin_task_role.id

  provider = aws.region
}

moved {
  from = aws_iam_role_policy.admin_permissions_role[0]
  to   = aws_iam_role_policy.admin_permissions_role
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "admin_permissions_role" {
  statement {
    sid    = "${local.policy_region_prefix}DynamoDbAccess"
    effect = "Allow"

    actions = [
      "dynamodb:BatchGetItem",
      "dynamodb:DescribeTable",
      "dynamodb:DescribeTimeToLive",
      "dynamodb:GetItem",
      "dynamodb:GetRecords",
      "dynamodb:ListGlobalTables",
      "dynamodb:ListTables",
      "dynamodb:Query",
      "dynamodb:Scan",
    ]

    resources = [
      var.dynamodb_tables.actor_users_table.arn,
      "${var.dynamodb_tables.actor_users_table.arn}/index/*",
      var.dynamodb_tables.viewer_codes_table.arn,
      "${var.dynamodb_tables.viewer_codes_table.arn}/index/*",
      var.dynamodb_tables.viewer_activity_table.arn,
      "${var.dynamodb_tables.viewer_activity_table.arn}/index/*",
      var.dynamodb_tables.user_lpa_actor_map.arn,
      "${var.dynamodb_tables.user_lpa_actor_map.arn}/index/*",
      var.dynamodb_tables.stats_table.arn,
      "${var.dynamodb_tables.stats_table.arn}/index/*",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}LpaCollectionsAccess"
    effect = "Allow"

    actions = [
      "execute-api:Invoke",
    ]

    resources = [
      "arn:aws:execute-api:eu-west-1:${var.sirius_account_id}:*/*/GET/use-an-lpa/*",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}LpaCodesAccess"
    effect = "Allow"
    actions = [
      "execute-api:Invoke",
    ]
    resources = [
      "arn:aws:execute-api:eu-west-1:${var.sirius_account_id}:*/*/GET/healthcheck",
      "arn:aws:execute-api:eu-west-1:${var.sirius_account_id}:*/*/POST/exists",
      "arn:aws:execute-api:eu-west-1:${var.sirius_account_id}:*/*/POST/code",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}AllowSSMParameterAccess"
    effect = "Allow"
    actions = [
      "ssm:GetParameter",
      "ssm:PutParameter",
    ]
    resources = var.parameter_store_arns
  }

  provider = aws.region
}

//-----------------------------------------------
// admin ECS Service Task Container level config

locals {
  admin_app = jsonencode(
    {
      cpu         = 1,
      essential   = true,
      image       = "${data.aws_ecr_repository.use_an_lpa_admin_app.repository_url}:${var.admin_container_version}",
      mountPoints = [],
      name        = "app",
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
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${var.environment_name}.admin-app.use-an-lpa"
        }
      },
      environment = [
        {
          name  = "LOGGING_LEVEL",
          value = tostring(var.logging_level)
        },
        {
          name  = "ADMIN_PORT",
          value = tostring(80)
        },
        {
          name  = "ADMIN_DYNAMODB_TABLE_PREFIX",
          value = tostring(var.environment_name)
        },
        {
          name  = "ADMIN_LOGOUT_URL",
          value = "${var.admin_cognito_user_pool_domain_name}/logout"
        },
        {
          name  = "ADMIN_JWT_SIGNING_KEY_URL",
          value = "https://public-keys.auth.elb.eu-west-1.amazonaws.com"
        },
        {
          name  = "ADMIN_CLIENT_ID",
          value = var.cognito_user_pool_id
        },
        {
          name  = "LPA_CODES_API_ENDPOINT",
          value = var.lpa_codes_endpoint
        },
      ]
    }
  )

}