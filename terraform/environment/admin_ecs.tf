//----------------------------------
// admin ECS Service level config

resource "aws_ecs_service" "admin" {
  count            = local.environment.build_admin ? 1 : 0
  name             = "admin-service"
  cluster          = aws_ecs_cluster.use-an-lpa.id
  task_definition  = aws_ecs_task_definition.admin[0].arn
  desired_count    = 1
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.admin_ecs_service[0].id]
    subnets          = data.aws_subnets.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.admin[0].arn
    container_name   = "app"
    container_port   = 80
  }

  capacity_provider_strategy {
    capacity_provider = local.capacity_provider
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

  depends_on = [aws_lb.admin]
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "admin_ecs_service" {
  count       = local.environment.build_admin ? 1 : 0
  name_prefix = "${local.environment_name}-admin-ecs-service"
  description = "Admin service security group"
  vpc_id      = data.aws_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }
}

// 80 in from the ELB
resource "aws_security_group_rule" "admin_ecs_service_ingress" {
  count                    = local.environment.build_admin ? 1 : 0
  description              = "Allow Port 80 ingress from the applciation load balancer"
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.admin_ecs_service[0].id
  source_security_group_id = aws_security_group.admin_loadbalancer[0].id
  lifecycle {
    create_before_destroy = true
  }
}

// Anything out
resource "aws_security_group_rule" "admin_ecs_service_egress" {
  count             = local.environment.build_admin ? 1 : 0
  description       = "Allow any egress from Use service"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:AWS007 - open egress for ECR access
  security_group_id = aws_security_group.admin_ecs_service[0].id
  lifecycle {
    create_before_destroy = true
  }
}

//--------------------------------------
// admin ECS Service Task level config

resource "aws_ecs_task_definition" "admin" {
  count                    = local.environment.build_admin ? 1 : 0
  family                   = "${local.environment_name}-admin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.admin_app}]"
  task_role_arn            = module.iam.ecs_task_roles.admin_task_role.arn
  execution_role_arn       = module.iam.ecs_execution_role.arn
}

resource "aws_iam_role_policy" "admin_permissions_role" {
  count  = local.environment.build_admin ? 1 : 0
  name   = "${local.environment_name}-${local.policy_region_prefix}-adminApplicationPermissions"
  policy = data.aws_iam_policy_document.admin_permissions_role.json
  role   = module.iam.ecs_task_roles.admin_task_role.id
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
      aws_dynamodb_table.actor_users_table.arn,
      "${aws_dynamodb_table.actor_users_table.arn}/index/*",
      aws_dynamodb_table.viewer_codes_table.arn,
      "${aws_dynamodb_table.viewer_codes_table.arn}/index/*",
      aws_dynamodb_table.viewer_activity_table.arn,
      "${aws_dynamodb_table.viewer_activity_table.arn}/index/*",
      aws_dynamodb_table.user_lpa_actor_map.arn,
      "${aws_dynamodb_table.user_lpa_actor_map.arn}/index/*",
      aws_dynamodb_table.stats_table.arn,
      "${aws_dynamodb_table.stats_table.arn}/index/*",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}LpaCollectionsAccess"
    effect = "Allow"

    actions = [
      "execute-api:Invoke",
    ]

    resources = [
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/GET/use-an-lpa/*",
    ]
  }

  statement {
    sid    = "${local.policy_region_prefix}LpaCodesAccess"
    effect = "Allow"
    actions = [
      "execute-api:Invoke",
    ]
    resources = [
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/GET/healthcheck",
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/POST/exists",
      "arn:aws:execute-api:eu-west-1:${local.environment.sirius_account_id}:*/*/POST/code",
    ]
  }
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
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = "eu-west-1",
          awslogs-stream-prefix = "${local.environment_name}.admin-app.use-an-lpa"
        }
      },
      environment = [
        {
          name  = "LOGGING_LEVEL",
          value = tostring(local.environment.logging_level)
        },
        {
          name  = "ADMIN_PORT",
          value = tostring(80)
        },
        {
          name  = "ADMIN_DYNAMODB_TABLE_PREFIX",
          value = tostring(local.environment_name)
        },
        {
          name  = "ADMIN_LOGOUT_URL",
          value = "${local.admin_cognito_user_pool_domain_name}/logout"
        },
        {
          name  = "ADMIN_JWT_SIGNING_KEY_URL",
          value = "https://public-keys.auth.elb.eu-west-1.amazonaws.com"
        },
        {
          name  = "ADMIN_CLIENT_ID",
          value = "${aws_cognito_user_pool_client.use_a_lasting_power_of_attorney_admin[0].id}"
        },
        {
          name  = "LPA_CODES_API_ENDPOINT",
          value = local.environment.lpa_codes_endpoint
        },
      ]
    }
  )

}

locals {
  admin_domain = local.environment.build_admin ? "https://${aws_route53_record.admin_use_my_lpa[0].fqdn}" : "Not deployed"
}

output "admin_domain" {
  value = local.admin_domain
}
