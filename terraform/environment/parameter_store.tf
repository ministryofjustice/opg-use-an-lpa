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