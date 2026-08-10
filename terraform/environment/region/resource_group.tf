resource "aws_resourcegroups_group" "environment" {
  name        = "${var.default_tags["environment-name"]}-environment-${var.region_name}"
  description = "Environment level eu-west-1 resources"

  resource_query {
    query = local.environment_resource_group_query
    type  = "TAG_FILTERS_1_0"
  }
  provider = aws.region
}

locals {
  environment_resource_group_query = jsonencode({
    ResourceTypeFilters = [
      "AWS::AllSupported"
    ],
    TagFilters = [
      {
        Key    = "environment-name",
        Values = [var.default_tags["environment-name"]]
      }
    ]
  })
}
