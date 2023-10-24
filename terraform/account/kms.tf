module "sessions_viewer_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "Managers keys for sessions in Viewer"
  key_alias               = "sessions-viewer-mrk"
  deletion_window_in_days = 7

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}

module "sessions_actor_mrk" {
  source = "./modules/multi_region_kms"

  key_description         = "Managers keys for sessions in Actor"
  key_alias               = "sessions-actor-mrk"
  deletion_window_in_days = 7

  providers = {
    aws.primary   = aws.eu_west_1
    aws.secondary = aws.eu_west_2
  }
}

# No longer used but kept to keep regional KMS keys
resource "aws_kms_key" "sessions_viewer" {
  description             = "Managers keys for sessions in Viewer"
  deletion_window_in_days = 7
  enable_key_rotation     = true
}

resource "aws_kms_alias" "sessions_viewer" {
  name          = "alias/sessions-viewer"
  target_key_id = aws_kms_key.sessions_viewer.key_id
}

resource "aws_kms_key" "sessions_actor" {
  description             = "Managers keys for sessions in Actor"
  deletion_window_in_days = 7
  enable_key_rotation     = true
}

resource "aws_kms_alias" "sessions_actor" {
  name          = "alias/sessions-actor"
  target_key_id = aws_kms_key.sessions_actor.key_id
}
