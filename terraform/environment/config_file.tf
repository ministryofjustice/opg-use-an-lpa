resource "local_file" "cluster_config" {
  content  = jsonencode(local.cluster_config)
  filename = "${path.module}/cluster_config.json"
}

locals {
  cluster_config = {
    actor_users_table                        = aws_dynamodb_table.actor_users_table.name
    cluster_name                             = aws_ecs_cluster.use-an-lpa.name
    account_id                               = local.environment.account_id
    actor_lpa_codes_table                    = aws_dynamodb_table.actor_codes_table.name
    viewer_codes_table                       = aws_dynamodb_table.viewer_codes_table.name
    user_lpa_actor_map                       = aws_dynamodb_table.user_lpa_actor_map.name
    actor_fqdn                               = aws_route53_record.actor-use-my-lpa.fqdn
    viewer_fqdn                              = aws_route53_record.viewer-use-my-lpa.fqdn
    admin_fqdn                               = local.environment.build_admin == true ? aws_route53_record.admin_use_my_lpa[0].fqdn : ""
    public_facing_use_fqdn                   = aws_route53_record.public_facing_use_lasting_power_of_attorney.fqdn
    public_facing_view_fqdn                  = aws_route53_record.public_facing_view_lasting_power_of_attorney.fqdn
    viewer_load_balancer_security_group_name = aws_security_group.viewer_loadbalancer.name
    actor_load_balancer_security_group_name  = aws_security_group.actor_loadbalancer.name
  }
}
