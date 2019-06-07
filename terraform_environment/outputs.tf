resource "local_file" "cluster_config" {
  content  = "${jsonencode(local.cluster_config)}"
  filename = "${path.module}/cluster_config.json"
}

locals {
  cluster_config = {
    cluster_name         = "${aws_ecs_cluster.use-an-lpa.name}"
    account_id           = "${local.account_id}"
    viewer_codes_table   = "${aws_dynamodb_table.viewer_codes_table.name}"
  }
}
