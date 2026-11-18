resource "aws_cloudwatch_log_metric_filter" "dynamodb_deletion_protection" {
  count          = local.environment.dynamodb_alarms_enabled ? 1 : 0
  name           = "${local.environment_name}-dynamodb-deletion-protection"
  log_group_name = "opg_use_an_lpa_cloudtrail_${local.environment.account_name}"
  pattern        = "{ ($.eventName = UpdateTable) && (($.requestParameters.deletionProtectionEnabled = true) || ($.requestParameters.deletionProtectionEnabled = false)) }"

  metric_transformation {
    name          = "DeletionProtectionChange"
    namespace     = "UseAnLPA/DynamoDB"
    value         = "1"
    default_value = 0
  }
}

resource "aws_cloudwatch_metric_alarm" "dynamodb_deletion_protection" {
  count               = local.environment.dynamodb_alarms_enabled ? 1 : 0
  alarm_name          = "${local.environment_name}-dynamodb-deletion-protection"
  alarm_description   = "DynamoDB deletion protection change requested."
  metric_name         = "DeletionProtectionChange"
  namespace           = "UseAnLPA/DynamoDB"
  statistic           = "Sum"
  period              = 60
  evaluation_periods  = 1
  threshold           = 1
  comparison_operator = "GreaterThanOrEqualToThreshold"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
  ok_actions          = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
}

resource "aws_cloudwatch_log_metric_filter" "dynamodb_export" {
  count          = local.environment.dynamodb_alarms_enabled ? 1 : 0
  name           = "${local.environment_name}-dynamodb-export"
  log_group_name = "opg_use_an_lpa_cloudtrail_${local.environment.account_name}"
  pattern        = "{ $.eventName = ExportTableToPointInTime }"

  metric_transformation {
    name          = "Export"
    namespace     = "UseAnLPA/DynamoDB"
    value         = "1"
    default_value = 0
  }
}

resource "aws_cloudwatch_metric_alarm" "dynamodb_export" {
  count               = local.environment.dynamodb_alarms_enabled ? 1 : 0
  alarm_name          = "${local.environment_name}-dynamodb-export"
  alarm_description   = "DynamoDB table export requested."
  metric_name         = "Export"
  namespace           = "UseAnLPA/DynamoDB"
  statistic           = "Sum"
  period              = 60
  evaluation_periods  = 1
  threshold           = 1
  comparison_operator = "GreaterThanOrEqualToThreshold"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
  ok_actions          = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
}

resource "aws_cloudwatch_log_metric_filter" "dynamodb_restore" {
  count          = local.environment.dynamodb_alarms_enabled ? 1 : 0
  name           = "${local.environment_name}-dynamodb-restore"
  log_group_name = "opg_use_an_lpa_cloudtrail_${local.environment.account_name}"
  pattern        = "{ ($.eventName = RestoreTableFromBackup) || ($.eventName = RestoreTableToPointInTime) || ($.eventName = StartRestoreJob) }"

  metric_transformation {
    name          = "Restore"
    namespace     = "UseAnLPA/DynamoDB"
    value         = "1"
    default_value = 0
  }
}

resource "aws_cloudwatch_metric_alarm" "dynamodb_restore" {
  count               = local.environment.dynamodb_alarms_enabled ? 1 : 0
  alarm_name          = "${local.environment_name}-dynamodb-restore"
  alarm_description   = "DynamoDB or AWS Backup restore requested."
  metric_name         = "Restore"
  namespace           = "UseAnLPA/DynamoDB"
  statistic           = "Sum"
  period              = 60
  evaluation_periods  = 1
  threshold           = 1
  comparison_operator = "GreaterThanOrEqualToThreshold"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
  ok_actions          = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
}

# Nightly backups are made by AWS Backup using the backup role, so those are excluded.
resource "aws_cloudwatch_log_metric_filter" "dynamodb_manual_backup" {
  count          = local.environment.dynamodb_alarms_enabled ? 1 : 0
  name           = "${local.environment_name}-dynamodb-manual-backup"
  log_group_name = "opg_use_an_lpa_cloudtrail_${local.environment.account_name}"
  pattern        = "{ ((($.eventSource = dynamodb.amazonaws.com) && ($.eventName = CreateBackup)) || ($.eventName = StartBackupJob)) && (($.userIdentity.invokedBy NOT EXISTS) || ($.userIdentity.invokedBy != backup.amazonaws.com)) && (($.userIdentity.sessionContext.sessionIssuer.arn NOT EXISTS) || ($.userIdentity.sessionContext.sessionIssuer.arn != \"arn:aws:iam::${local.environment.account_id}:role/aws_backup_role\")) }"

  metric_transformation {
    name          = "ManualBackup"
    namespace     = "UseAnLPA/DynamoDB"
    value         = "1"
    default_value = 0
  }
}

resource "aws_cloudwatch_metric_alarm" "dynamodb_manual_backup" {
  count               = local.environment.dynamodb_alarms_enabled ? 1 : 0
  alarm_name          = "${local.environment_name}-dynamodb-manual-backup"
  alarm_description   = "Backup requested outside the scheduled backup plan."
  metric_name         = "ManualBackup"
  namespace           = "UseAnLPA/DynamoDB"
  statistic           = "Sum"
  period              = 60
  evaluation_periods  = 1
  threshold           = 1
  comparison_operator = "GreaterThanOrEqualToThreshold"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
  ok_actions          = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
}

resource "aws_cloudwatch_log_metric_filter" "dynamodb_manual_copy" {
  count          = local.environment.dynamodb_alarms_enabled ? 1 : 0
  name           = "${local.environment_name}-dynamodb-manual-copy"
  log_group_name = "opg_use_an_lpa_cloudtrail_${local.environment.account_name}"
  pattern        = "{ ($.eventName = StartCopyJob) && (($.userIdentity.invokedBy NOT EXISTS) || ($.userIdentity.invokedBy != backup.amazonaws.com)) && (($.userIdentity.sessionContext.sessionIssuer.arn NOT EXISTS) || ($.userIdentity.sessionContext.sessionIssuer.arn != \"arn:aws:iam::${local.environment.account_id}:role/aws_backup_role\")) }"

  metric_transformation {
    name          = "ManualCopy"
    namespace     = "UseAnLPA/DynamoDB"
    value         = "1"
    default_value = 0
  }
}

resource "aws_cloudwatch_metric_alarm" "dynamodb_manual_copy" {
  count               = local.environment.dynamodb_alarms_enabled ? 1 : 0
  alarm_name          = "${local.environment_name}-dynamodb-manual-copy"
  alarm_description   = "Backup copy requested outside the scheduled backup plan."
  metric_name         = "ManualCopy"
  namespace           = "UseAnLPA/DynamoDB"
  statistic           = "Sum"
  period              = 60
  evaluation_periods  = 1
  threshold           = 1
  comparison_operator = "GreaterThanOrEqualToThreshold"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
  ok_actions          = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
}

resource "aws_cloudwatch_log_metric_filter" "dynamodb_copy_destination" {
  count          = local.environment.dynamodb_alarms_enabled ? 1 : 0
  name           = "${local.environment_name}-dynamodb-copy-destination"
  log_group_name = "opg_use_an_lpa_cloudtrail_${local.environment.account_name}"
  pattern        = "{ ($.eventName = CopyJobStarted) && ($.serviceEventDetails.destinationBackupVaultArn != \"arn:aws:backup:*:${local.environment.account_id}:backup-vault:*_dynamodb_vault\") && ($.serviceEventDetails.destinationBackupVaultArn != \"arn:aws:backup:*:238302996107:backup-vault:opg_use_an_lpa_*_backup\") }"

  metric_transformation {
    name          = "UnexpectedCopyDestination"
    namespace     = "UseAnLPA/DynamoDB"
    value         = "1"
    default_value = 0
  }
}

resource "aws_cloudwatch_metric_alarm" "dynamodb_copy_destination" {
  count               = local.environment.dynamodb_alarms_enabled ? 1 : 0
  alarm_name          = "${local.environment_name}-dynamodb-copy-destination"
  alarm_description   = "Backup copy started to an unapproved vault."
  metric_name         = "UnexpectedCopyDestination"
  namespace           = "UseAnLPA/DynamoDB"
  statistic           = "Sum"
  period              = 60
  evaluation_periods  = 1
  threshold           = 1
  comparison_operator = "GreaterThanOrEqualToThreshold"
  treat_missing_data  = "notBreaching"
  alarm_actions       = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
  ok_actions          = [module.eu_west_1[0].cloudwatch_alarm_topic_arn]
}
