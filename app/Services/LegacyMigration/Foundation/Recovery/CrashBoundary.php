<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

enum CrashBoundary: string
{
    case BeforeNumberAllocation = 'PILOT-RESUME-001';
    case AfterNumberAllocationBeforePatientCommit = 'PILOT-RESUME-002';
    case AfterPatientCommitBeforeCrosswalkCommit = 'PILOT-RESUME-003';
    case AfterCoreCommitBeforeCheckpoint = 'PILOT-RESUME-004';
    case DuringAliasCreation = 'PILOT-RESUME-005';
    case DuringContactCreation = 'PILOT-RESUME-006';
    case DuringInsuranceHistory = 'PILOT-RESUME-007';
    case DuringInsuranceCurrent = 'PILOT-RESUME-008';
    case AfterTargetWritesBeforeReconciliation = 'PILOT-RESUME-009';
    case AfterReconciliationBeforeCompletion = 'PILOT-RESUME-010';
}
