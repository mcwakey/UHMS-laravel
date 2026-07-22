<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

enum RecoveryDisposition: string
{
    case RetryFromClassified = 'retry_from_classified';
    case ReuseExactAllocation = 'reuse_exact_allocation';
    case RetryRolledBackUnit = 'retry_rolled_back_unit';
    case RepairCheckpointOnly = 'repair_checkpoint_only';
    case ResumeAliasUnit = 'resume_alias_unit';
    case ResumeContactUnit = 'resume_contact_unit';
    case ResumeMissingHistoryRows = 'resume_missing_history_rows';
    case ResumeCurrentGroup = 'resume_current_group';
    case RepairReconciliationOnly = 'repair_reconciliation_only';
    case RepairCompletionOnly = 'repair_completion_only';
    case StopAndReclassify = 'stop_and_reclassify';
    case ReconciliationFailed = 'reconciliation_failed';
    case CompensationRequired = 'compensation_required';
}
