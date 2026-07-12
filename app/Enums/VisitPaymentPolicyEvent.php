<?php

namespace App\Enums;

/**
 * Material transition recorded in the immutable visit-payment-policy history
 * (Payment Timing Policy Phase 6). Used for human-readable, localised rendering;
 * history rows are never edited or deleted through the application.
 */
enum VisitPaymentPolicyEvent: string
{
    case MATERIALIZED = 'materialized';
    case REFRESHED = 'refreshed';
    case RISK_SNAPSHOT_CHANGED = 'risk_snapshot_changed';
    case BASELINE_POLICY_CHANGED = 'baseline_policy_changed';
    case RECOMMENDATION_CHANGED = 'recommendation_changed';
    case MARKED_STALE = 'marked_stale';

    public function label(): string
    {
        return __('visit_payment_policy.events.'.$this->value);
    }

    public function activityAction(): string
    {
        return 'VISIT_PAYMENT_POLICY_'.strtoupper($this->value);
    }
}
