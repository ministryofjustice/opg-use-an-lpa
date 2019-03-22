resource "aws_ecs_service" "view" {
  name            = "view"
  cluster         = "${aws_ecs_cluster.use-an-lpa.id}"
  task_definition = "${aws_ecs_task_definition.view.arn}"
  desired_count   = 1
  launch_type     = "FARGATE"

  network_configuration {
    security_groups  = ["${aws_security_group.ecs_service.id}"]
    subnets          = ["${aws_subnet.private.*.id}"]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = "${aws_lb_target_group.view.arn}"
    container_name   = "web"
    container_port   = 80
  }
}

resource "aws_security_group" "ecs_service" {
  name   = "ecs-service"
  vpc_id = "${aws_default_vpc.default.id}"
  tags   = "${local.default_tags}"

  ingress {
    protocol        = "tcp"
    from_port       = 80
    to_port         = 80
    security_groups = ["${aws_security_group.view_loadbalancer.id}"]
  }

  egress {
    protocol    = "-1"
    from_port   = 0
    to_port     = 0
    cidr_blocks = ["0.0.0.0/0"]
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_ecs_task_definition" "view" {
  family                   = "view"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.web}, ${local.app}]"
  task_role_arn            = "${aws_iam_role.use_an_lpa.arn}"
  execution_role_arn       = "${aws_iam_role.execution_role.arn}"
}

resource "aws_iam_role" "use_an_lpa" {
  name               = "view"
  assume_role_policy = "${data.aws_iam_policy_document.task_role_assume_policy.json}"
}

data "aws_ecr_repository" "use_my_lpa_web" {
  provider = "aws.management"
  name     = "use_my_lpa/web"
}

data "aws_ecr_repository" "use_my_lpa_view" {
  provider = "aws.management"
  name     = "use_my_lpa/view_lpa_front"
}

locals {
  web = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.use_my_lpa_web.repository_url}:latest",
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
            "awslogs-group": "${aws_cloudwatch_log_group.use-an-lpa.name}",
            "awslogs-region": "eu-west-2",
            "awslogs-stream-prefix": "view.use-an-lpa"
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

  app = <<EOF
  {
    "cpu": 1,
    "essential": true,
    "image": "${data.aws_ecr_repository.use_my_lpa_view.repository_url}:latest",
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
            "awslogs-group": "${aws_cloudwatch_log_group.use-an-lpa.name}",
            "awslogs-region": "eu-west-2",
            "awslogs-stream-prefix": "view.use-an-lpa"
        }
    }
  }
  EOF
}
