data "aws_ssm_parameter" "gotenberg_version" {
  count    = var.use_gotenberg_latest_green ? 1 : 0
  name     = "/opg-gotenberg-latest-green-build"
  provider = aws.shared_production
}

resource "aws_ssm_parameter" "container_version" {
  name     = "/use-an-lpa/container-version/${local.environment_name}"
  type     = "String"
  value    = var.container_version
  provider = aws.management
}

resource "aws_ssm_parameter" "system_message_view_en" {
  name  = "/system-message/${local.environment_name}/view/en"
  type  = "String"
  value = " "

  lifecycle {
    ignore_changes = [
      value
    ]
  }
}

resource "aws_ssm_parameter" "system_message_view_cy" {
  name  = "/system-message/${local.environment_name}/view/cy"
  type  = "String"
  value = " "

  lifecycle {
    ignore_changes = [
      value
    ]
  }
}

resource "aws_ssm_parameter" "system_message_use_en" {
  name  = "/system-message/${local.environment_name}/use/en"
  type  = "String"
  value = " "

  lifecycle {
    ignore_changes = [
      value
    ]
  }
}

resource "aws_ssm_parameter" "system_message_use_cy" {
  name  = "/system-message/${local.environment_name}/use/cy"
  type  = "String"
  value = " "

  lifecycle {
    ignore_changes = [
      value
    ]
  }
}
