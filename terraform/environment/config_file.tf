resource "local_file" "cluster_config" {
  content  = jsonencode(local.cluster_config)
  filename = "${path.module}/cluster_config.json"
}

locals {
  cluster_config = {
    cluster_name = aws_ecs_cluster.use-an-lpa.name
    account_id   = local.account.account_id

    actor_lpa_codes_table = aws_dynamodb_table.actor_codes_table.name
    viewer_codes_table    = aws_dynamodb_table.viewer_codes_table.name
    actor_users_table     = aws_dynamodb_table.actor_users_table.name

    actor_fqdn  = aws_route53_record.actor-use-my-lpa.fqdn
    viewer_fqdn = aws_route53_record.viewer-use-my-lpa.fqdn
  }
}
