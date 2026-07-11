<?php

use App\Enums\VisitPaymentTimingPolicy;

return [
    // Phase 1 is configuration-only. No service gate reads this flag yet.
    'enabled' => false,
    'integration' => [
        'mode' => env('PAYMENT_TIMING_INTEGRATION_MODE', 'legacy'),
        'log_mismatches' => env('PAYMENT_TIMING_LOG_MISMATCHES', true),
        'log_matches' => env('PAYMENT_TIMING_LOG_MATCHES', false),
        'deduplication_seconds' => env('PAYMENT_TIMING_LOG_DEDUPLICATION_SECONDS', 300),
    ],
    'default_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
    'visit_types' => [
        'outpatient' => VisitPaymentTimingPolicy::INHERIT->value,
        'inpatient' => VisitPaymentTimingPolicy::RUNNING_BILL->value,
        'emergency' => VisitPaymentTimingPolicy::RUNNING_BILL->value,
    ],
    'emergency' => [
        'never_block_stabilisation' => true,
        'default_policy' => VisitPaymentTimingPolicy::RUNNING_BILL->value,
    ],
    'financial_closure' => [
        'require_settlement_for_pay_after_services' => true,
        'require_settlement_for_running_bill' => true,
        'allow_authorised_outstanding_balance_override' => true,
    ],
    'risk' => [
        'normal_policy' => VisitPaymentTimingPolicy::INHERIT->value,
        'watchlist_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
        'high_risk_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
        'blocked_credit_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
    ],
];
