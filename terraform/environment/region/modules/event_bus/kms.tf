data "aws_kms_alias" "event_receiver_mrk" {
  name     = "alias/${var.environment_name}-event-receiver-mrk"
  provider = aws.region
}
