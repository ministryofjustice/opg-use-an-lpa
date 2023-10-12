resource "local_file" "cluster_config" {
  content  = jsonencode(local.cluster_config)
  filename = "${path.module}/cluster_config.json"
}

locals {
  cluster_config = {
    actor_users_table                        = aws_dynamodb_table.actor_users_table.name
    cluster_name                             = module.eu_west_1.ecs_cluster.name
    account_id                               = local.environment.account_id
    actor_lpa_codes_table                    = aws_dynamodb_table.actor_codes_table.name
    viewer_codes_table                       = aws_dynamodb_table.viewer_codes_table.name
    user_lpa_actor_map                       = aws_dynamodb_table.user_lpa_actor_map.name
    stats_table                              = aws_dynamodb_table.stats_table.name
    actor_fqdn                               = aws_route53_record.actor_use_my_lpa.fqdn
    viewer_fqdn                              = aws_route53_record.viewer_use_my_lpa.fqdn
    admin_fqdn                               = aws_route53_record.admin_use_my_lpa.fqdn
    public_facing_use_fqdn                   = aws_route53_record.public_facing_use_lasting_power_of_attorney.fqdn
    public_facing_view_fqdn                  = aws_route53_record.public_facing_view_lasting_power_of_attorney.fqdn
    viewer_load_balancer_security_group_name = module.eu_west_1.security_group_names.viewer_loadbalancer
    actor_load_balancer_security_group_name  = module.eu_west_1.security_group_names.actor_loadbalancer

  }
}
