resource "local_file" "cluster_config" {
  content  = jsonencode(local.cluster_config)
  filename = "${path.module}/cluster_config.json"
}

locals {

  active_region = [for k, v in local.environment.regions : k if v["is_active"] == true][0]

  cluster_config = {
    actor_users_table                        = aws_dynamodb_table.actor_users_table.name
    cluster_name                             = local.active_region == "eu-west-1" ? module.eu_west_1.ecs_cluster.name : module.eu_west_2.ecs_cluster.name
    account_id                               = local.environment.account_id
    actor_lpa_codes_table                    = aws_dynamodb_table.actor_codes_table.name
    viewer_codes_table                       = aws_dynamodb_table.viewer_codes_table.name
    user_lpa_actor_map                       = aws_dynamodb_table.user_lpa_actor_map.name
    stats_table                              = aws_dynamodb_table.stats_table.name
    actor_fqdn                               = local.active_region == "eu-west-1" ? module.eu_west_1.route53_fqdns.actor : module.eu_west_2.route53_fqdns.actor
    viewer_fqdn                              = local.active_region == "eu-west-1" ? module.eu_west_1.route53_fqdns.viewer : module.eu_west_2.route53_fqdns.viewer
    admin_fqdn                               = local.active_region == "eu-west-1" ? module.eu_west_1.route53_fqdns.admin : module.eu_west_2.route53_fqdns.admin
    public_facing_use_fqdn                   = local.active_region == "eu-west-1" ? module.eu_west_1.route53_fqdns.public_facing_use : module.eu_west_2.route53_fqdns.public_facing_use
    public_facing_view_fqdn                  = local.active_region == "eu-west-1" ? module.eu_west_1.route53_fqdns.public_facing_view : module.eu_west_2.route53_fqdns.public_facing_view
    viewer_load_balancer_security_group_name = local.active_region == "eu-west-1" ? module.eu_west_1.security_group_names.viewer_loadbalancer : module.eu_west_2.security_group_names.viewer_loadbalancer
    actor_load_balancer_security_group_name  = local.active_region == "eu-west-1" ? module.eu_west_1.security_group_names.actor_loadbalancer : module.eu_west_2.security_group_names.actor_loadbalancer
    active_region                            = local.active_region
  }
}