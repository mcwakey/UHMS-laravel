<?php

namespace App\Enums;

/**
 * Administrative provenance of a per-visit payment arrangement (Payment Timing
 * Policy Phase 7). Metadata only — it makes no operational payment decision.
 */
enum VisitPaymentArrangementSource: string
{
    case MANUAL_REQUEST = 'manual_request';
    case RISK_RECOMMENDATION = 'risk_recommendation';
    case FINANCE_DECISION = 'finance_decision';
    case MANAGEMENT_DECISION = 'management_decision';
    case CREDIT_APPROVAL = 'credit_approval';
    case CORPORATE_GUARANTEE = 'corporate_guarantee';
    case INSURANCE_AUTHORIZATION = 'insurance_authorization';
    case BASELINE_RESTORATION = 'baseline_restoration';

    public function label(): string
    {
        return __('visit_payment_arrangement.sources.'.$this->value);
    }

    /** @return array<int, self> */
    public static function selectable(): array
    {
        return self::cases();
    }
}
