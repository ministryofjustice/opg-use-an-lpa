resource "aws_security_group" "brute_force_cache_service" {
  name_prefix = "brute-force-cache-service"
  description = "brute force cache sg"
  vpc_id      = aws_default_vpc.default.id
  tags        = local.default_tags
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_elasticache_subnet_group" "private_subnets" {
  name       = "private-subnets"
  subnet_ids = aws_subnet.private[*].id
}


resource "aws_elasticache_replication_group" "brute_force_cache_replication_group" {
  automatic_failover_enabled    = true
  replication_group_id          = "brute-force-cache-replication-group"
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
