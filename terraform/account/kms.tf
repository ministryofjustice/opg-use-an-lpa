module "sessions_viewer_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "Managers keys for sessions in Viewer"
  key_alias               = "sessions-viewer"
  deletion_window_in_days = 7

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}

module "sessions_actor_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "Managers keys for sessions in Actor"
  key_alias               = "sessions-actor"
  deletion_window_in_days = 7

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}
