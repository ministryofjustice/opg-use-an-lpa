resource "aws_secretsmanager_secret" "notify_api_key" {
  name = "notify-api-key"

  tags   = "${local.default_tags}"
}
data "aws_secretsmanager_secret" "notify_api_key" {
  name = "notify-api-key"
}

data "aws_secretsmanager_secret_version" "notify_api_key" {
  secret_id = "${aws_secretsmanager_secret.notify_api_key.id}"
}
