resource "local_file" "cluster_config" {
  content  = jsonencode(local.cluster_config)
  filename = "${path.module}/cluster_config.json"
}

locals {

  active_region = [for k, v in local.environment.regions : k if v["is_active"] == true][0]

  cluster_config = {
    use_users_table                                 = aws_dynamodb_table.use_users_table.name
    cluster_name                                    = local.active_region == "eu-west-1" ? module.eu_west_1[0].ecs_cluster.name : module.eu_west_2[0].ecs_cluster.name
    account_id                                      = local.environment.account_id
    use_lpa_codes_table                             = aws_dynamodb_table.use_codes_table.name
    viewer_codes_table                              = aws_dynamodb_table.viewer_codes_table.name
    user_lpa_actor_map                              = aws_dynamodb_table.user_lpa_actor_map.name
    stats_table                                     = aws_dynamodb_table.stats_table.name
    ff_paper_verification                           = local.environment.application_flags.paper_verification
    use_fqdn                                        = local.active_region == "eu-west-1" ? module.eu_west_1[0].route53_fqdns.use : module.eu_west_2[0].route53_fqdns.use
    viewer_fqdn                                     = local.active_region == "eu-west-1" ? module.eu_west_1[0].route53_fqdns.viewer : module.eu_west_2[0].route53_fqdns.viewer
    admin_fqdn                                      = local.active_region == "eu-west-1" ? module.eu_west_1[0].route53_fqdns.admin : module.eu_west_2[0].route53_fqdns.admin
    one_login_mock_fqdn                             = local.active_region == "eu-west-1" ? module.eu_west_1[0].route53_fqdns.mock_onelogin : module.eu_west_2[0].route53_fqdns.mock_onelogin
    public_facing_use_fqdn                          = local.active_region == "eu-west-1" ? module.eu_west_1[0].route53_fqdns.public_facing_use : module.eu_west_2[0].route53_fqdns.public_facing_use
    public_facing_view_fqdn                         = local.active_region == "eu-west-1" ? module.eu_west_1[0].route53_fqdns.public_facing_view : module.eu_west_2[0].route53_fqdns.public_facing_view
    viewer_load_balancer_security_group_name        = local.active_region == "eu-west-1" ? module.eu_west_1[0].security_group_names.viewer_loadbalancer : module.eu_west_2[0].security_group_names.viewer_loadbalancer
    actor_load_balancer_security_group_name         = local.active_region == "eu-west-1" ? module.eu_west_1[0].security_group_names.actor_loadbalancer : module.eu_west_2[0].security_group_names.actor_loadbalancer
    mock_onelogin_load_balancer_security_group_name = local.active_region == "eu-west-1" ? module.eu_west_1[0].security_group_names.mock_onelogin_loadbalancer : module.eu_west_2[0].security_group_names.mock_onelogin_loadbalancer
    viewer_load_balancer_security_group_id          = local.active_region == "eu-west-1" ? module.eu_west_1[0].security_group_ids.viewer_loadbalancer : module.eu_west_2[0].security_group_id.viewer_loadbalancer
    actor_load_balancer_security_group_id           = local.active_region == "eu-west-1" ? module.eu_west_1[0].security_group_ids.actor_loadbalancer : module.eu_west_2[0].security_group_id.actor_loadbalancer
    mock_onelogin_load_balancer_security_group_id   = local.active_region == "eu-west-1" ? module.eu_west_1[0].security_group_ids.mock_onelogin_loadbalancer : module.eu_west_2[0].security_group_id.mock_onelogin_loadbalancer
    active_region                                   = local.active_region
    vpc_id                                          = local.active_region == "eu-west-1" ? module.eu_west_1[0].vpc_id : module.eu_west_2[0].vpc_id
  }
}
