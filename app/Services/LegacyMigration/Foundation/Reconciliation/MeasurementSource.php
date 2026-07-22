<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

enum MeasurementSource: string
{
    case DatabaseBeforeAfter = 'database_before_after';
    case TransactionWriteSet = 'transaction_write_set';
    case QueryRecorder = 'query_recorder';
    case SideEffectCounter = 'side_effect_counter';
    case SourceReadOnlyRecorder = 'source_read_only_recorder';
    case TargetMutationObserver = 'target_mutation_observer';
    case ProtectedRepository = 'protected_repository';
    case RuntimeAudit = 'runtime_audit';
    case SnapshotEvidence = 'snapshot_evidence';
}
