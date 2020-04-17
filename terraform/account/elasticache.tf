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
