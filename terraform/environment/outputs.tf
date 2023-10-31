output "admin_domain" {
  value = "https://${module.eu_west_1.route53_fqdns.admin}"
}

output "public_facing_use_domain" {
  value = "https://${module.eu_west_1.route53_fqdns.public_facing_use}"
}

output "public_facing_view_domain" {
  value = "https://${module.eu_west_1.route53_fqdns.public_facing_view}"
}
