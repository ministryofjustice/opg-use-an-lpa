resource "aws_security_group" "brute_force_cache_service" {
  name_prefix = "brute-force-cache-service"
  description = "brute force cache sg"
  vpc_id      = data.aws_default_tags.current.tags.environment-name != "production" ? module.network.vpc.id : aws_default_vpc.default.id
  lifecycle {
    create_before_destroy = true
  }

  provider = aws.region
}

resource "aws_elasticache_subnet_group" "private_subnets" {
  name       = "private-subnets"
  subnet_ids = aws_subnet.private[*].id

  provider = aws.region
}

resource "aws_elasticache_subnet_group" "private_subnets_fwn" {
  name       = "private-subnets-fwn"
  subnet_ids = module.network.application_subnets[*].id

  provider = aws.region
}

resource "aws_elasticache_replication_group" "brute_force_cache_replication_group" {
  automatic_failover_enabled = true
  replication_group_id       = "brute-force-cache-replication-group"
  description                = "brute force redis cache replication group"
  parameter_group_name       = "default.redis7"
  engine_version             = "7.0"
  node_type                  = "cache.t4g.micro"
  engine                     = "redis"
  num_cache_clusters         = 2
  transit_encryption_enabled = true
  at_rest_encryption_enabled = true
  subnet_group_name          = data.aws_default_tags.current.tags.environment-name != "production" ? aws_elasticache_subnet_group.private_subnets_fwn.name : aws_elasticache_subnet_group.private_subnets.name
  security_group_ids         = [aws_security_group.brute_force_cache_service.id]

  provider = aws.region
}
