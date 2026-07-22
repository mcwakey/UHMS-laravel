<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

enum MigrationState: string
{
    case NotStarted = 'NOT_STARTED';
    case Extracted = 'EXTRACTED';
    case Classified = 'CLASSIFIED';
    case DryRunAccepted = 'DRY_RUN_ACCEPTED';
    case CoreCommitting = 'CORE_COMMITTING';
    case CoreCommitted = 'CORE_COMMITTED';
    case AliasPending = 'ALIAS_PENDING';
    case AliasCommitted = 'ALIAS_COMMITTED';
    case AliasWithheld = 'ALIAS_WITHHELD';
    case ContactPending = 'CONTACT_PENDING';
    case ContactCommitted = 'CONTACT_COMMITTED';
    case ContactWithheld = 'CONTACT_WITHHELD';
    case InsuranceHistoryPending = 'INSURANCE_HISTORY_PENDING';
    case InsuranceHistoryCommitted = 'INSURANCE_HISTORY_COMMITTED';
    case InsuranceCurrentPending = 'INSURANCE_CURRENT_PENDING';
    case InsuranceCurrentCommitted = 'INSURANCE_CURRENT_COMMITTED';
    case InsuranceCurrentWithheld = 'INSURANCE_CURRENT_WITHHELD';
    case ReconciliationPending = 'RECONCILIATION_PENDING';
    case ReconciliationPassed = 'RECONCILIATION_PASSED';
    case ReconciliationFailed = 'RECONCILIATION_FAILED';
    case Quarantined = 'QUARANTINED';
    case RollbackRequired = 'ROLLBACK_REQUIRED';
    case CompensationRequired = 'COMPENSATION_REQUIRED';
    case Completed = 'COMPLETED';
}
