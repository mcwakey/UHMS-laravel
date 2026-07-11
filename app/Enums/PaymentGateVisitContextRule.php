<?php

namespace App\Enums;

/**
 * How a registered operation relates visit-context (visit payment-timing policy)
 * to its payment gate. Configuration and diagnostics only in Phase 4 — it must
 * not replace legacy operational decisions.
 */
enum PaymentGateVisitContextRule: string
{
    case USE_VISIT_POLICY = 'use_visit_policy';
    case ALWAYS_RUNNING_BILL = 'always_running_bill';
    case PRESERVE_LEGACY = 'preserve_legacy';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return __('payment_gate.visit_context.'.$this->value);
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
