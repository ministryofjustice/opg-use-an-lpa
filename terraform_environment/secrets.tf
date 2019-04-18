resource "aws_secretsmanager_secret" "session_key" {
  name = "${terraform.workspace}-session-keys"

  recovery_window_in_days = 0
  tags                    = "${local.default_tags}"
}

resource "aws_secretsmanager_secret_version" "session_key_init_value" {
  secret_id = "${aws_secretsmanager_secret.session_key.id}"

  secret_string = <<EOF
  {
    "1":"0000000000000000000000000000000000000000000000000000000000000000",
    "2":"1111111111111111111111111111111111111111111111111111111111111111"
  }
  EOF
}
