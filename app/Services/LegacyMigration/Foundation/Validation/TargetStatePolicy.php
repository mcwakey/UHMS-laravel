<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final readonly class TargetStatePolicy
{
    /** @param array<int, TargetStateRule> $rules */
    private function __construct(
        public string $version,
        public string $authority,
        public array $rules,
        public string $fingerprint,
    ) {
        if (trim($version) === '' || trim($authority) === '' || $rules === [] || preg_match('/\A[a-f0-9]{64}\z/', $fingerprint) !== 1) {
            throw new ValidationException('FOUNDATION_TARGET_POLICY_INVALID', [], 'A versioned target-state policy is required.');
        }
    }

    public static function patientPhase2F(): self
    {
        $approvedNull = [TargetFieldClassification::ApprovedValue];
        $conditionalNull = [TargetFieldClassification::ApprovedConditionalNull];
        $pending = [
            TargetFieldClassification::RecommendationPendingApproval,
            TargetFieldClassification::ProhibitedDefault,
            TargetFieldClassification::CommitBlocker,
        ];

        $rules = [
            new TargetStateRule('PILOT-STATE-001', 'status', $pending, null, ['LEGACY-PATIENT-STATUS-044', 'LEGACY-PATIENT-STATUS-045']),
            new TargetStateRule('PILOT-STATE-002', 'is_active', $pending, null, ['LEGACY-PATIENT-STATUS-044', 'LEGACY-PATIENT-STATUS-046']),
            new TargetStateRule('PILOT-STATE-003', 'is_temporary', $pending, null, ['LEGACY-PATIENT-STATUS-044', 'LEGACY-PATIENT-STATUS-047']),
            new TargetStateRule('PILOT-STATE-004', 'temporary_reason', $conditionalNull, null, ['LEGACY-PATIENT-STATUS-047']),
            new TargetStateRule('PILOT-STATE-005', 'identity_confirmed_at', $approvedNull, null, ['LEGACY-PATIENT-STATUS-044']),
            new TargetStateRule('PILOT-STATE-006', 'identity_confirmed_by', $approvedNull, null, ['LEGACY-PATIENT-ACTOR-032']),
            new TargetStateRule('PILOT-STATE-007', 'merge_status', $pending, null, ['LEGACY-PATIENT-STATUS-044', 'LEGACY-PATIENT-STATUS-048']),
            new TargetStateRule('PILOT-STATE-008', 'merged_to_patient_id', $conditionalNull, null, ['LEGACY-PATIENT-STATUS-048']),
            new TargetStateRule('PILOT-STATE-009', 'merged_at', $conditionalNull, null, ['LEGACY-PATIENT-STATUS-048']),
            new TargetStateRule('PILOT-STATE-010', 'merged_by', $conditionalNull, null, ['LEGACY-PATIENT-STATUS-048']),
            new TargetStateRule('PILOT-STATE-011', 'is_deceased', $pending, null, ['LEGACY-PATIENT-STATUS-044', 'LEGACY-PATIENT-STATUS-049']),
            new TargetStateRule('PILOT-STATE-012', 'deceased_at', $conditionalNull, null, ['LEGACY-PATIENT-STATUS-049']),
            new TargetStateRule('PILOT-STATE-013', 'cause_of_death', $approvedNull, null, ['LEGACY-PATIENT-STATUS-049']),
            new TargetStateRule('PILOT-STATE-014', 'deceased_notes', $approvedNull, null, ['LEGACY-PATIENT-STATUS-049']),
            new TargetStateRule('PILOT-STATE-015', 'marked_deceased_by', $approvedNull, null, ['LEGACY-PATIENT-ACTOR-032']),
            new TargetStateRule('PILOT-STATE-016', 'registered_by', $approvedNull, null, ['LEGACY-PATIENT-ACTOR-032']),
            new TargetStateRule('PILOT-STATE-017', 'deleted_at', $pending, null, ['LEGACY-PATIENT-STATUS-044', 'LEGACY-PATIENT-DELETE-043']),
            new TargetStateRule('PILOT-STATE-018', 'created_at', [TargetFieldClassification::ApprovedValue], self::SOURCE_MAPPED_VALUE, ['LEGACY-PATIENT-REMEDIATION-040']),
            new TargetStateRule('PILOT-STATE-019', 'updated_at', $pending, null, ['LEGACY-PATIENT-STATUS-044']),
            new TargetStateRule('PILOT-STATE-020', 'complete_state_tuple', [TargetFieldClassification::CommitBlocker], null, ['LEGACY-PATIENT-STATUS-044']),
        ];

        return self::trusted('patient-state/2F.1.0', 'phase-2f/patient_pilot_state_matrix.json@2F.1.0:owner-approval-blocked', $rules);
    }

    public static function insurancePhase2F(): self
    {
        $null = [TargetFieldClassification::ApprovedValue];
        $blocker = [
            TargetFieldClassification::RecommendationPendingApproval,
            TargetFieldClassification::ProhibitedDefault,
            TargetFieldClassification::CommitBlocker,
        ];

        $rules = [
            new TargetStateRule('PILOT-INS-INIT-001', 'patient_id', [TargetFieldClassification::ApprovedValue], self::SOURCE_MAPPED_VALUE, ['LEGACY-INSURANCE-PATIENT-005']),
            new TargetStateRule('PILOT-INS-INIT-002', 'insurance_provider_id', [TargetFieldClassification::ApprovedValue], self::SOURCE_MAPPED_VALUE, ['LEGACY-INSURANCE-PROVIDER-007']),
            new TargetStateRule('PILOT-INS-INIT-003', 'insurance_tier_id', $null, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-004', 'member_type', $blocker, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-005', 'card_holder_insurance_id', $null, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-006', 'membership_number', [TargetFieldClassification::ApprovedValue], self::OPTIONAL_SOURCE_MAPPED_VALUE, ['LEGACY-INSURANCE-MEMBER-019']),
            new TargetStateRule('PILOT-INS-INIT-007', 'policy_number', $null, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-008', 'ccc_code', $null, null, ['LEGACY-INSURANCE-ELIGIBILITY-030']),
            new TargetStateRule('PILOT-INS-INIT-009', 'start_date', [TargetFieldClassification::ApprovedValue], self::OPTIONAL_SOURCE_MAPPED_VALUE, ['LEGACY-INSURANCE-DATE-020']),
            new TargetStateRule('PILOT-INS-INIT-010', 'expiry_date', [TargetFieldClassification::ApprovedValue], self::OPTIONAL_SOURCE_MAPPED_VALUE, ['LEGACY-INSURANCE-DATE-023']),
            new TargetStateRule('PILOT-INS-INIT-011', 'is_primary', $blocker, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-012', 'is_active', $blocker, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-013', 'created_at', $blocker, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-013', 'updated_at', $blocker, null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
            new TargetStateRule('PILOT-INS-INIT-014', 'verification', [TargetFieldClassification::TargetOwnedOperationalState, TargetFieldClassification::ProhibitedDefault], null, ['LEGACY-INSURANCE-ELIGIBILITY-028']),
            new TargetStateRule('PILOT-INS-INIT-014', 'eligibility', [TargetFieldClassification::TargetOwnedOperationalState, TargetFieldClassification::ProhibitedDefault], null, ['LEGACY-INSURANCE-ELIGIBILITY-028']),
            new TargetStateRule('PILOT-INS-INIT-015', 'current_representation', [TargetFieldClassification::CommitBlocker], null, ['LEGACY-INSURANCE-CONSOLIDATION-026']),
        ];

        return self::trusted('insurance-initialization/2F.1.0', 'phase-2f/insurance_pilot_initialization_rules.json@2F.1.0:owner-approval-blocked', $rules);
    }

    public const SOURCE_MAPPED_VALUE = '__SOURCE_MAPPED__';

    public const OPTIONAL_SOURCE_MAPPED_VALUE = '__OPTIONAL_SOURCE_MAPPED__';

    /** @param array<int, TargetStateRule> $rules */
    private static function trusted(string $version, string $authority, array $rules): self
    {
        $material = array_map(static fn (TargetStateRule $rule): array => [
            'rule_id' => $rule->ruleId,
            'field' => $rule->field,
            'classifications' => array_map(static fn (TargetFieldClassification $item): string => $item->value, $rule->classifications),
            'approved_value' => $rule->approvedValue,
            'exception_codes' => $rule->exceptionCodes,
            'approval_reference' => $rule->approvalReference,
        ], $rules);
        $fingerprint = hash('sha256', json_encode([
            'version' => $version,
            'authority' => $authority,
            'rules' => $material,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return new self($version, $authority, $rules, $fingerprint);
    }
}
