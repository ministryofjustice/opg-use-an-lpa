//----------------------------------
// Actor ECS Service level config

resource "aws_ecs_service" "actor" {
  name            = "actor"
  cluster         = aws_ecs_cluster.use-an-lpa.id
  task_definition = aws_ecs_task_definition.actor.arn
  desired_count   = 1
  launch_type     = "FARGATE"

  network_configuration {
    security_groups  = [aws_security_group.actor_ecs_service.id]
    subnets          = data.aws_subnet_ids.private.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.actor.arn
    container_name   = "web"
    container_port   = 80
  }

  depends_on = [aws_lb.actor]
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "actor_ecs_service" {
  name_prefix = "${local.environment}-actor-ecs-service"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

// 80 in from the ELB
resource "aws_security_group_rule" "actor_ecs_service_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = aws_security_group.actor_ecs_service.id
  source_security_group_id = aws_security_group.actor_loadbalancer.id
}

// Anything out
resource "aws_security_group_rule" "actor_ecs_service_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.actor_ecs_service.id
}

//--------------------------------------
// Actor ECS Service Task level config

resource "aws_ecs_task_definition" "actor" {
  family                   = "${local.environment}-actor"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.actor_web}, ${local.actor_app}]"
  task_role_arn            = aws_iam_role.actor_task_role.arn
  execution_role_arn       = aws_iam_role.execution_role.arn
  tags                     = local.default_tags
}

//----------------
// Permissions

resource "aws_iam_role" "actor_task_role" {
  name               = "${local.environment}-actor-task-role"
  assume_role_policy = data.aws_iam_policy_document.task_role_assume_policy.json
  tags               = local.default_tags
}

resource "aws_iam_role_policy" "actor_permissions_role" {
  name   = "${local.environment}-ActorApplicationPermissions"
  policy = data.aws_iam_policy_document.actor_permissions_role.json
  role   = aws_iam_role.actor_task_role.id
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "actor_permissions_role" {
  statement {
    effect = "Allow"

    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]

    resources = [data.aws_kms_alias.sessions_actor.target_key_arn]
  }
}

//-----------------------------------------------
// Actor ECS Service Task Container level config

locals {
  actor_web = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.use_an_lpa_front_web.repository_url}:${var.container_version}",
    "mountPoints": [],
    "name": "web",
    "portMappings": [
        {
            "containerPort": 80,
            "hostPort": 80,
            "protocol": "tcp"
        }
    ],
    "volumesFrom": [],
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
            "awslogs-group": "${data.aws_cloudwatch_log_group.use-an-lpa.name}",
            "awslogs-region": "eu-west-1",
            "awslogs-stream-prefix": "actor-web.use-an-lpa"
        }
    },
    "environment": [
    {
      "name": "APP_HOST",
      "value": "127.0.0.1"
    },
    {
      "name": "APP_PORT",
      "value": "9000"
    },
    {
      "name": "TIMEOUT",
      "value": "60"
    },
    {
      "name": "CONTAINER_VERSION",
      "value": "${var.container_version}"
    }]
  }
  
EOF


  actor_app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.use_an_lpa_front_app.repository_url}:${var.container_version}",
    "mountPoints": [],
    "name": "app",
    "portMappings": [
        {
            "containerPort": 9000,
            "hostPort": 9000,
            "protocol": "tcp"
        }
    ],
    "volumesFrom": [],
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
            "awslogs-group": "${data.aws_cloudwatch_log_group.use-an-lpa.name}",
            "awslogs-region": "eu-west-1",
            "awslogs-stream-prefix": "actor-app.use-an-lpa"
        }
    },
    "secrets" : [
    {
      "name": "NOTIFY_API_KEY",
      "valueFrom": "${data.aws_secretsmanager_secret.notify_api_key.arn}"
    }],
    "environment": [
    {
      "name": "CONTEXT",
      "value": "actor"
    },
    {
      "name": "KMS_SESSION_CMK_ALIAS",
      "value": "${data.aws_kms_alias.sessions_actor.name}"
    },
    {
      "name": "CONTAINER_VERSION",
      "value": "${var.container_version}"
    },
    {
      "name": "API_SERVICE_URL",
      "value": "http://${local.api_service_fqdn}"
    }]
  }
  
EOF

}

output "front_web_deployed_version" {
  value = "${data.aws_ecr_repository.use_an_lpa_front_web.repository_url}:${var.container_version}"
}

output "front_app_deployed_version" {
  value = "${data.aws_ecr_repository.use_an_lpa_front_app.repository_url}:${var.container_version}"
}

