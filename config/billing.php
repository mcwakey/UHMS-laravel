<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Manual discount policy
    |--------------------------------------------------------------------------
    |
    | Users with billing.discount.apply may discount invoice items up to this
    | percentage of the line total. Larger discounts require
    | billing.discount.override_limit and are logged as high-risk actions.
    |
    */
    'discount' => [
        'max_without_override_percent' => env('BILLING_DISCOUNT_MAX_WITHOUT_OVERRIDE_PERCENT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maternity billing posting safeguards
    |--------------------------------------------------------------------------
    |
    | Maternity billing starts in preview-only mode. Posting is deliberately
    | disabled by default so clinical maternity records cannot create charges
    | until mappings, duplicate guards, and site billing policy are approved.
    |
    */
    'maternity_billing' => [
        'enabled' => env('MATERNITY_BILLING_ENABLED', false),
        'auto_post' => env('MATERNITY_BILLING_AUTO_POST', false),
        'newborn_billing_policy' => env('MATERNITY_NEWBORN_BILLING_POLICY', 'mother'),

        /*
        | Phase 14R.6 — de-duplication POLICY only. Advisory and read-only:
        | nothing here posts, suppresses or reverses a charge. It exists so the
        | overlap between an event-specific Consultation Specialty charge and a
        | Maternity event charge is visible BEFORE Phase 14.2 posting starts.
        |
        | The base consultation attendance fee is never treated as a duplicate
        | of a clinical maternity event.
        */
        'deduplication_policy_enabled' => env(
            'MATERNITY_BILLING_DEDUPLICATION_POLICY_ENABLED',
            false
        ),

        'allow_both_when_configured' => env(
            'MATERNITY_BILLING_ALLOW_BOTH_WHEN_CONFIGURED',
            false
        ),

        'allow_manual_selection' => env(
            'MATERNITY_BILLING_ALLOW_MANUAL_SELECTION',
            false
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Previous visit outstanding balance policy
    |--------------------------------------------------------------------------
    |
    | Governs how debt carried over from a patient's PREVIOUS visits is surfaced
    | and settled. Read by App\Services\PatientOutstandingBalanceService,
    | PatientPaymentAllocationService and PreviousBalanceOverrideService.
    |
    | Golden rules (enforced in code, not just config):
    |   * Each visit keeps its OWN invoice and balance — old lines are never
    |     merged into the current visit invoice.
    |   * The patient ACCOUNT balance is the sum of all unpaid invoice balances.
    |   * Emergency care is NEVER blocked by previous debt.
    |   * No old revenue is recognised again; collecting old debt only reduces the
    |     existing receivable (Dr Cash / Cr Patient Receivables).
    |
    */
    'previous_balance_policy' => [
        'enabled' => env('PREVIOUS_BALANCE_POLICY_ENABLED', true),

        // Where the warning surfaces (UI advisory — never blocks by itself).
        'show_warning_on_visit_creation' => true,
        'show_warning_on_billing' => true,
        'show_warning_on_consultation' => true,

        // OPD gate: a previous balance above this amount requires an authorised
        // billing/supervisor override before non-emergency service proceeds.
        'opd_requires_override_above_amount' => (float) env('PREVIOUS_BALANCE_OPD_OVERRIDE_THRESHOLD', 100.00),
        // When true, ANY previous balance (however small) requires an override.
        'opd_requires_override_if_any_previous_balance' => (bool) env('PREVIOUS_BALANCE_OPD_OVERRIDE_ANY', false),

        // Safety rails.
        'emergency_never_blocked_by_previous_balance' => true,
        'admission_show_previous_balance_on_discharge' => true,

        // Default cross-visit payment allocation strategy. One of:
        //   oldest_first | current_visit_first | manual
        'default_payment_allocation' => env('PREVIOUS_BALANCE_ALLOCATION', 'oldest_first'),

        // Behaviour when a tender exceeds total patient balance:
        //   reject  → refuse the surplus (default, no deposit workflow yet)
        //   ignore  → allocate what fits, caller keeps the change
        'overpayment_behaviour' => env('PREVIOUS_BALANCE_OVERPAYMENT', 'reject'),
    ],
];
