output "fqdn" {
  value = aws_route53_record.this.fqdn
}

output "health_check_id" {
  value       = aws_route53_health_check.this[0].id
  description = "The ID of the health check"
}
