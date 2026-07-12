<?php

namespace App\Enums;

/**
 * Structured reason a patient financial-risk classification was recorded
 * (Payment Timing Policy Phase 5). Free-text details remain supplementary; a
 * reason is never inferred automatically in this phase.
 */
enum PatientFinancialRiskReason: string
{
    case PREVIOUS_UNPAID_VISITS = 'previous_unpaid_visits';
    case REPEATED_ABANDONED_INVOICES = 'repeated_abandoned_invoices';
    case CREDIT_LIMIT_EXCEEDED = 'credit_limit_exceeded';
    case INVALID_CORPORATE_GUARANTEE = 'invalid_corporate_guarantee';
    case INSURANCE_ELIGIBILITY_UNRESOLVED = 'insurance_eligibility_unresolved';
    case PAYMENT_COMMITMENT_BREACHED = 'payment_commitment_breached';
    case MANAGEMENT_DECISION = 'management_decision';
    case OTHER = 'other';

    public function label(): string
    {
        return __('patient_financial_risk.reasons.'.$this->value);
    }

    /** Reasons that mandate explanatory free-text or a reference. */
    public function requiresDetails(): bool
    {
        return match ($this) {
            self::OTHER, self::MANAGEMENT_DECISION => true,
            default => false,
        };
    }

    /** @return array<int, self> */
    public static function selectable(): array
    {
        return self::cases();
    }
}
