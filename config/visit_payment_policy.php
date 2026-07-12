<?php

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentTimingPolicy;

/*
|--------------------------------------------------------------------------
| Visit Payment Policy Materialisation (Payment Timing Policy Phase 6)
|--------------------------------------------------------------------------
|
| These settings control the OBSERVATIONAL visit-payment-policy materialisation
| subsystem. Nothing here activates enforcement: risk rules are provided for
| diagnostics/preparation only and every level is `approved_for_resolution =
| false`. A stored/config value must NEVER make a recommendation operational.
|
*/

return [
    // Auto-materialise an observational policy record when a visit is created.
    // Failures never block visit creation (see VisitObserver).
    'auto_materialize' => true,

    // Stable machine identifier for the materialisation algorithm. Bump this
    // (never a timestamp) when the algorithm changes.
    'resolution_version' => 'payment_timing_v1',

    // Provisional, NON-OPERATIONAL risk-to-policy rules (mirrors Phase 4 safety).
    // approved_for_resolution is false for every level in Phase 6.
    'risk_rules' => [
        PatientFinancialRiskLevel::NORMAL->value => [
            'recommended_policy' => null,
            'requires_finance_review' => false,
            'approved_for_resolution' => false,
        ],
        PatientFinancialRiskLevel::WATCHLIST->value => [
            'recommended_policy' => null,
            'requires_finance_review' => true,
            'approved_for_resolution' => false,
        ],
        PatientFinancialRiskLevel::HIGH_RISK->value => [
            'recommended_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
            'requires_finance_review' => true,
            'approved_for_resolution' => false,
        ],
        PatientFinancialRiskLevel::BLOCKED_CREDIT->value => [
            'recommended_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
            'requires_finance_review' => true,
            'approved_for_resolution' => false,
        ],
    ],
];
