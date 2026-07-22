<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

enum IdempotencyOutcomeType: string
{
    case PatientCore = 'patient_core';
    case ExistingTargetLink = 'existing_target_link';
    case PatientNumber = 'patient_number';
    case LegacyOpdAlias = 'legacy_opd_alias';
    case EmergencyContact = 'emergency_contact';
    case InlineDemographics = 'inline_demographics';
    case InsuranceHistoryRow = 'insurance_history_row';
    case InsurancePatientProviderGroup = 'insurance_patient_provider_group';
    case ExceptionRecord = 'exception_record';
    case ReconciliationResult = 'reconciliation_result';
    case Checkpoint = 'checkpoint';
    case Drift = 'drift';
    case QuarantineChain = 'quarantine_chain';
}
