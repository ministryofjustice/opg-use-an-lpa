//----------------------------------
// Viewer ECS Service level config

resource "aws_ecs_service" "viewer" {
  name            = "${terraform.workspace}-viewer"
  cluster         = "${aws_ecs_cluster.use-an-lpa.id}"
  task_definition = "${aws_ecs_task_definition.viewer.arn}"
  desired_count   = 1
  launch_type     = "FARGATE"

  network_configuration {
    security_groups  = ["${aws_security_group.ecs_service.id}"]
    subnets          = ["${data.aws_subnet.private.*.id}"]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = "${aws_lb_target_group.viewer.arn}"
    container_name   = "web"
    container_port   = 80
  }

  # depends_on = ["aws_lb.viewer", "aws_acm_certificate.cert", "aws_acm_certificate_validation.cert"]
  depends_on = ["aws_lb.viewer"]
}

//----------------------------------
// The service's Security Groups

resource "aws_security_group" "ecs_service" {
  name_prefix = "${terraform.workspace}-viewer-ecs-service"
  vpc_id      = "${data.aws_vpc.default.id}"
  tags        = "${local.default_tags}"
}

// 80 in from the ELB
resource "aws_security_group_rule" "ecs_service_ingress" {
  type                     = "ingress"
  from_port                = 80
  to_port                  = 80
  protocol                 = "tcp"
  security_group_id        = "${aws_security_group.ecs_service.id}"
  source_security_group_id = "${aws_security_group.viewer_loadbalancer.id}"
}

// Anything out
resource "aws_security_group_rule" "ecs_service_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = "${aws_security_group.ecs_service.id}"
}

//--------------------------------------
// Viewer ECS Service Task level config

resource "aws_ecs_task_definition" "viewer" {
  family                   = "${terraform.workspace}-viewer"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.viewer_web}, ${local.viewer_app}]"
  task_role_arn            = "${aws_iam_role.use_an_lpa.arn}"
  execution_role_arn       = "${aws_iam_role.execution_role.arn}"
  tags                     = "${local.default_tags}"
}

//----------------
// Permissions

resource "aws_iam_role" "use_an_lpa" {
  name               = "${terraform.workspace}-viewer-task-role"
  assume_role_policy = "${data.aws_iam_policy_document.task_role_assume_policy.json}"
  tags               = "${local.default_tags}"
}

resource "aws_iam_role_policy" "use_an_lpa_execution_role" {
  name   = "${terraform.workspace}-ViewerApplicationPermissions"
  policy = "${data.aws_iam_policy_document.use_an_lpa_execution_role.json}"
  role   = "${aws_iam_role.use_an_lpa.id}"
}

/*
  Defines permissions that the application running within the task has.
*/
data "aws_iam_policy_document" "use_an_lpa_execution_role" {
  statement {
    effect = "Allow"

    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
    ]

    resources = ["${data.aws_kms_alias.sessions_viewer.target_key_arn}"]
  }
}

//--------------------
// ECR Repos

data "aws_ecr_repository" "use_an_lpa_web" {
  provider = "aws.management"
  name     = "use_an_lpa/web"
}

data "aws_ecr_repository" "use_an_lpa_view" {
  provider = "aws.management"
  name     = "use_an_lpa/viewer_front"
}

//-----------------------------------------------
// Viewer ECS Service Task Container level config

locals {
  viewer_web = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.use_an_lpa_web.repository_url}:${var.container_version}",
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
            "awslogs-stream-prefix": "viewer-web.use-an-lpa"
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
    }]
  }
  EOF

  viewer_app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.use_an_lpa_view.repository_url}:${var.container_version}",
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
            "awslogs-stream-prefix": "viewer-app.use-an-lpa"
        }
    },
    "environment": [
    {
      "name": "KMS_SESSION_CMK_ALIAS",
      "value": "${data.aws_kms_alias.sessions_viewer.name}"
    }]
  }
  EOF
}

output "web_deployed_version" {
  value = "${data.aws_ecr_repository.use_an_lpa_web.repository_url}:${var.container_version}"
}

output "viewer_deployed_version" {
  value = "${data.aws_ecr_repository.use_an_lpa_view.repository_url}:${var.container_version}"
}
