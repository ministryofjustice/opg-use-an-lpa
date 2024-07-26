output "fqdn" {
  value = aws_route53_record.this.fqdn
}

output "health_check_id" {
  value       = var.create_health_check ? aws_route53_health_check.this[0].id : null
  description = "The ID of the health check"
}
