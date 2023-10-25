output "key_arn" {
  value       = aws_kms_key.this.arn
  description = "The Amazon Resource Name (ARN) of the replica key. The key ARNs of related multi-Region keys differ only in the Region value."
}

output "alias_arn" {
  value       = aws_kms_alias.primary_alias.arn
  description = "The Amazon Resource Name (ARN) of the primary key alias."
}

output "replica_key_arn" {
  value       = aws_kms_replica_key.this.arn
  description = "The Amazon Resource Name (ARN) of the replica key. The key ARNs of related multi-Region keys differ only in the Region value."
}

output "replica_alias_arn" {
  value       = aws_kms_alias.secondary_alias.arn
  description = "The Amazon Resource Name (ARN) of the replica key alias."
}

output "key_id" {
  value       = aws_kms_key.this.key_id
  description = "The key ID of the replica key. Related multi-Region keys have the same key ID."
}
