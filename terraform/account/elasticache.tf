resource "aws_security_group" "brute_force_cache_service" {
  name   = "brute-force-cache-service"
  vpc_id = aws_default_vpc.default.id
  tags   = local.default_tags
}

resource "aws_elasticache_subnet_group" "private_subnets" {
  name       = "private-subnets"
  subnet_ids = aws_subnet.private[*].id
}


resource "aws_elasticache_cluster" "brute_force_cache" {
  cluster_id           = "brute-force-cache"
  engine               = "redis"
  node_type            = "cache.t2.micro"
  num_cache_nodes      = 1
  parameter_group_name = "default.redis5.0"
  engine_version       = "5.0.6"

  subnet_group_name  = aws_elasticache_subnet_group.private_subnets.name
  security_group_ids = [aws_security_group.brute_force_cache_service.id]

  tags = local.default_tags
}


resource "aws_elasticache_replication_group" "brute_force_cache_rg" {
  automatic_failover_enabled    = true
  replication_group_id          = "brute-force-cache-rg"
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
