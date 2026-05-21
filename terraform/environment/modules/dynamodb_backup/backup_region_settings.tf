resource "aws_backup_region_settings" "source" {
  resource_type_opt_in_preference = {
    DynamoDB = true
  }

  resource_type_management_preference = {
    DynamoDB = true
  }
}

resource "aws_backup_region_settings" "destination" {
  region = var.replica_region

  resource_type_opt_in_preference = {
    DynamoDB = true
  }

  resource_type_management_preference = {
    DynamoDB = true
  }
}
