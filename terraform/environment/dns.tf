# data "aws_route53_zone" "opg_service_justice_gov_uk" {
#   provider = aws.management
#   name     = "opg.service.justice.gov.uk"
# }

# data "aws_route53_zone" "live_service_use_lasting_power_of_attorney" {
#   provider = aws.management
#   name     = "use-lasting-power-of-attorney.service.gov.uk"
# }

# data "aws_route53_zone" "live_service_view_lasting_power_of_attorney" {
#   provider = aws.management
#   name     = "view-lasting-power-of-attorney.service.gov.uk"
# }

# resource "aws_service_discovery_private_dns_namespace" "intrnal_ecs" {
#   name = "${local.environment_name}.ual.internal.ecs"
#   vpc  = data.aws_vpc.default.id
# }

# //-------------------------------------------------------------
# // View

# resource "aws_route53_record" "public_facing_view_lasting_power_of_attorney" {
#   # view-lasting-power-of-attorney.service.gov.uk
#   provider = aws.management
#   zone_id  = data.aws_route53_zone.live_service_view_lasting_power_of_attorney.zone_id
#   name     = "${local.dns_namespace_env}${data.aws_route53_zone.live_service_view_lasting_power_of_attorney.name}"
#   type     = "A"

#   alias {
#     evaluate_target_health = false
#     name                   = module.eu_west_1.albs.viewer.dns_name
#     zone_id                = module.eu_west_1.albs.viewer.zone_id
#   }

#   lifecycle {
#     create_before_destroy = true
#   }
# }

# output "public_facing_view_domain" {
#   value = "https://${aws_route53_record.public_facing_view_lasting_power_of_attorney.fqdn}"
# }

# resource "aws_route53_record" "viewer_use_my_lpa" {
#   # view.lastingpowerofattorney.opg.service.justice.gov.uk
#   provider = aws.management
#   zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
#   name     = "${local.dns_namespace_env}view.lastingpowerofattorney"
#   type     = "A"

#   alias {
#     evaluate_target_health = false
#     name                   = module.eu_west_1.albs.viewer.dns_name
#     zone_id                = module.eu_west_1.albs.viewer.zone_id
#   }

#   lifecycle {
#     create_before_destroy = true
#   }
# }

# moved {
#   from = aws_route53_record.viewer-use-my-lpa
#   to   = aws_route53_record.viewer_use_my_lpa
# }

# //-------------------------------------------------------------
# // Use

# resource "aws_route53_record" "public_facing_use_lasting_power_of_attorney" {
#   # use-lasting-power-of-attorney.service.gov.uk
#   provider = aws.management
#   zone_id  = data.aws_route53_zone.live_service_use_lasting_power_of_attorney.zone_id
#   name     = "${local.dns_namespace_env}${data.aws_route53_zone.live_service_use_lasting_power_of_attorney.name}"
#   type     = "A"

#   alias {
#     evaluate_target_health = false
#     name                   = module.eu_west_1.albs.actor.dns_name
#     zone_id                = module.eu_west_1.albs.actor.zone_id
#   }
#   lifecycle {
#     create_before_destroy = true
#   }
# }

# output "public_facing_use_domain" {
#   value = "https://${aws_route53_record.public_facing_use_lasting_power_of_attorney.fqdn}"
# }

# resource "aws_route53_record" "actor_use_my_lpa" {
#   # use.lastingpowerofattorney.opg.service.justice.gov.uk
#   provider = aws.management
#   zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
#   name     = "${local.dns_namespace_env}use.lastingpowerofattorney"
#   type     = "A"

#   alias {
#     evaluate_target_health = false
#     name                   = module.eu_west_1.albs.actor.dns_name
#     zone_id                = module.eu_west_1.albs.actor.zone_id
#   }

#   lifecycle {
#     create_before_destroy = true
#   }
# }

# moved {
#   from = aws_route53_record.actor-use-my-lpa
#   to   = aws_route53_record.actor_use_my_lpa
# }

# resource "aws_route53_record" "admin_use_my_lpa" {
#   # admin.lastingpowerofattorney.opg.service.justice.gov.uk
#   provider = aws.management
#   zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
#   name     = "${local.dns_namespace_env}admin.lastingpowerofattorney"
#   type     = "A"

#   alias {
#     evaluate_target_health = false
#     name                   = module.eu_west_1.albs.admin.dns_name
#     zone_id                = module.eu_west_1.albs.admin.zone_id
#   }

#   lifecycle {
#     create_before_destroy = true
#   }
# }

# moved {
#   from = aws_route53_record.admin_use_my_lpa[0]
#   to   = aws_route53_record.admin_use_my_lpa
# }
