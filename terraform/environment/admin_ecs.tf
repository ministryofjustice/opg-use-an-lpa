//----------------------------------
// admin ECS Service level config

resource "aws_ecs_service" "admin" {
  count            = local.account.build_admin == true ? 1 : 0
  name             = "admin"
  cluster          = aws_ecs_cluster.use-an-lpa.id
  task_definition  = aws_ecs_task_definition.admin[0].arn
  desired_count    = 1
  launch_type      = "FARGATE"
  platform_version = "1.4.0"

  network_configuration {
    security_groups  = [aws_security_group.admin_ecs_service[0].id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.admin[0].arn
    container_name   = "app"
    container_port   = 80
  }

  wait_for_steady_state = true

  depends_on = [aws_lb.admin]
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "admin_ecs_service" {
  count       = local.account.build_admin == true ? 1 : 0
  name_prefix = "${local.environment}-admin-ecs-service"
  description = "Admin service security group"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
  lifecycle {
    create_before_destroy = true
  }
}

// 80 in from the ELB
resource "aws_security_group_rule" "admin_ecs_service_ingress" {
  count                    = local.account.build_admin == true ? 1 : 0
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
  count             = local.account.build_admin == true ? 1 : 0
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
  count                    = local.account.build_admin == true ? 1 : 0
  family                   = "${local.environment}-admin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.admin_app}]"
  task_role_arn            = aws_iam_role.admin_task_role[0].arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}

//----------------
// Permissions

resource "aws_iam_role" "admin_task_role" {
  count              = local.account.build_admin == true ? 1 : 0
  name               = "${local.environment}-admin-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
  tags               = local.default_tags
}

resource "aws_iam_role_policy" "admin_permissions_role" {
  count  = local.account.build_admin == true ? 1 : 0
  name   = "${local.environment}-adminApplicationPermissions"
  policy = data.aws_iam_policy_document.admin_permissions_role.json
  role   = aws_iam_role.admin_task_role[0].id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "admin_permissions_role" {
  statement {
    sid    = "dynamodbaccess"
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
    ]
  }

  statement {
    sid    = "lpacollectionsaccess"
    effect = "Allow"

    actions = [
      "execute-api:Invoke",
    ]

    resources = [
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/GET/use-an-lpa/*",
    ]
  }

  statement {
    sid    = "lpacodesaccess"
    effect = "Allow"
    actions = [
      "execute-api:Invoke",
    ]
    resources = [
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/GET/healthcheck",
      "arn:aws:execute-api:eu-west-1:${local.account.sirius_account_id}:*/*/POST/exists",
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
      image       = "${data.aws_ecr_repository.use_an_lpa_admin_app.repository_url}:${var.container_version}",
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
          awslogs-stream-prefix = "${local.environment}.admin-app.use-an-lpa"
        }
      },
      environment = [
        {
          name  = "LOGGING_LEVEL",
          value = tostring(local.account.logging_level)
        },
        {
          name  = "PORT",
          value = tostring(80)
        },
        {
          name  = "DYNAMODB_TABLE_PREFIX",
          value = tostring(local.environment)
        },
        {
          name  = "LOGOUT_URL",
          value = "${local.admin_cognito_user_pool_domain_name}/logout"
        }
      ]
    }
  )

}

locals {
  admin_domain = local.account.build_admin == true ? "https://${aws_route53_record.admin_use_my_lpa[0].fqdn}" : "Not deployed"
}

output "admin_domain" {
  value = local.admin_domain
}
