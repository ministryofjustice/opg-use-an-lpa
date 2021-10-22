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
