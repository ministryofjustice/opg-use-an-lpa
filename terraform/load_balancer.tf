resource "aws_elb" "view" {
  name               = "view-${terraform.workspace}"
  availability_zones = ["eu-west-2a"]

  listener {
    instance_port     = 80
    instance_protocol = "http"
    lb_port           = 80
    lb_protocol       = "http"
  }

  lifecycle {
    create_before_destroy = true
  }
}
