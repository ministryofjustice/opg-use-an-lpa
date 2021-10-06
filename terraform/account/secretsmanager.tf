resource "aws_secretsmanager_secret" "notify_api_key" {
  name = "notify-api-key"
  tags = local.default_tags
}
