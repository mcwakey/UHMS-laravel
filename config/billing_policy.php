<?php

/*
|--------------------------------------------------------------------------
| UHMS Billing / Payment Policy
|--------------------------------------------------------------------------
|
| Context-aware enforcement of "when may a service be rendered relative to
| payment". Read by App\Services\Billing\BillingPolicyService and
| PaymentGateService. These are the hospital-policy DEFAULTS; department- or
| service-level fields (when present) override them, and an active
| VisitBillingOverride (deferred settlement / credit / waiver) overrides both.
|
| Care contexts:
|   OPD        → strict "pay before service" by default (gated).
|   EMERGENCY  → running bill; care is never blocked by payment.
|   ADMISSION  → running bill; optional financial clearance before discharge.
|
| Nothing here blocks clinical care for Emergency/Admission. See
| docs/BILLING_PAYMENT_POLICY_IMPLEMENTATION_REPORT.md.
|
*/

return [

    // Master switch. When false, the payment gate ALLOWS everything (advisory
    // mode) — useful to roll the feature out gradually without blocking work.
    'enforce' => env('BILLING_GATE_ENFORCE', true),

    'opd' => [
        'payment_required_before_service' => true,
        'allow_deferred_visit_settlement' => true,
        'partial_payment_can_proceed' => false,
        'minimum_payment_percent' => 100,
    ],

    'emergency' => [
        'payment_required_before_service' => false,
        'running_bill' => true,
        'allow_periodic_payments' => true,
        'block_non_urgent_unpaid_services' => false,
    ],

    'admission' => [
        'payment_required_before_service' => false,
        'running_bill' => true,
        'allow_periodic_payments' => true,
        'require_clearance_before_discharge' => true,
    ],

    'insurance' => [
        'covered_services_can_proceed' => true,
        'copayment_must_be_settled_before_opd_service' => true,
    ],

    'credit' => [
        'credit_approved_services_can_proceed' => true,
    ],

    'waiver' => [
        'waived_services_can_proceed' => true,
    ],
];
