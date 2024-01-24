output "admin_domain" {
  value = "https://${local.cluster_config.admin_fqdn}"
}

output "public_facing_use_domain" {
  value = "https://${local.cluster_config.public_facing_use_fqdn}"
}

output "public_facing_view_domain" {
  value = "https://${local.cluster_config.public_facing_view_fqdn}"
}