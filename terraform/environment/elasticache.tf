# note this is temporary for testing the connection to PHP
resource "aws_elasticache_replication_group" "brute_force_cache" {
  automatic_failover_enabled    = true
  replication_group_id          = "brute-force-cache"
  replication_group_description = "brute force redis cache replication group"
  parameter_group_name          = "default.redis5.0"
  engine_version                = "5.0.6"
  node_type                     = "cache.t2.micro"
  engine                        = "redis"
  number_cache_clusters         = 2
  transit_encryption_enabled    = true
  at_rest_encryption_enabled    = true

  subnet_group_name  = aws_elasticache_subnet_group.private_subnets.name
  security_group_ids = [aws_security_group.brute_force_cache_service.id]

  tags = local.default_tags

}
