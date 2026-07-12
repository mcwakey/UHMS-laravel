<?php

return [
    'mode' => env('VISIT_FINANCIAL_CLEARANCE_MODE', 'disabled'),
    'force_disabled' => env('VISIT_FINANCIAL_CLEARANCE_FORCE_DISABLED', true),
    'financial_close' => [
        'require_reason' => true,
        'fallback_to_no_enforcement_on_failure' => true,
    ],
    'settlement' => [
        'currency_tolerance' => '0.01',
        'use_patient_responsibility_only' => true,
        'require_no_unbilled_billable_items' => true,
    ],
    'conditional_clearance' => [
        'enabled' => true,
        'require_separate_approver' => true,
        'allow_outstanding_balance' => true,
        'allow_payment_plan' => true,
        'allow_insurance_pending' => true,
        'allow_corporate_guarantee' => true,
    ],
    'automatic_refresh' => [
        'after_payment' => true,
        'mark_stale_after_new_charge' => true,
        'financially_close_automatically' => false,
    ],
];
