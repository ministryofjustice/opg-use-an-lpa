output "admin_domain" {
  value       = "https://${local.cluster_config.admin_fqdn}"
  description = "The URL of the admin interface"
}

output "public_facing_use_domain" {
  value       = "https://${local.cluster_config.public_facing_use_fqdn}"
  description = "The URL of the public facing use interface"
}

output "public_facing_view_domain" {
  value       = "https://${local.cluster_config.public_facing_view_fqdn}"
  description = "The URL of the public facing view interface"
}

output "container_version" {
  value       = var.container_version
  description = "The tag of the container image that has been deployed"
}

output "admin_container_version" {
  value       = var.admin_container_version
  description = "The tag of the admin container image that has been deployed"
}

output "workspace_name" {
  value       = terraform.workspace
  description = "The name of the Terraform workspace"
}
