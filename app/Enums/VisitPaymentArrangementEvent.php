<?php

namespace App\Enums;

/**
 * Material transition recorded in the immutable per-visit payment-arrangement
 * history (Payment Timing Policy Phase 7). Used for localised rendering; history
 * rows are never edited or deleted through the application.
 */
enum VisitPaymentArrangementEvent: string
{
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
    case REPLACED = 'replaced';
    case UPDATED_BEFORE_DECISION = 'updated_before_decision';
    case BASELINE_RESTORED = 'baseline_restored';

    public function label(): string
    {
        return __('visit_payment_arrangement.events.'.$this->value);
    }

    public function activityAction(): string
    {
        return match ($this) {
            self::BASELINE_RESTORED => 'VISIT_PAYMENT_BASELINE_RESTORED',
            self::UPDATED_BEFORE_DECISION => 'VISIT_PAYMENT_ARRANGEMENT_UPDATED',
            default => 'VISIT_PAYMENT_ARRANGEMENT_'.strtoupper($this->value),
        };
    }
}
