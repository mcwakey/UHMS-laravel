<?php

/*
|--------------------------------------------------------------------------
| UHMS UI / Design-System Configuration
|--------------------------------------------------------------------------
|
| The single source of truth for status → colour mapping across UHMS. The
| <x-status-badge> Blade component reads these maps so that the SAME workflow
| status always renders the SAME Bootstrap contextual colour, system-wide.
|
| Colours are Bootstrap 5 contextual variants (primary, secondary, success,
| warning, danger, info, dark, light) — UHMS ships Bootstrap, not Tailwind,
| so we reuse its tokens rather than inventing new ones.
|
| Semantic meaning (do NOT give one colour two meanings):
|   success  → completed / paid / verified / available / approved
|   info     → active / in-progress / due / current
|   warning  → pending / attention / low / held
|   danger   → critical / error / overdue / out / rejected
|   secondary→ inactive / cancelled / not-stocked / neutral
|   dark     → death / black-triage / high-contrast
|   primary  → brand / admitted / current focus
|
| See docs/UHMS_UI_THEME_RULES.md for the human-readable design law.
|
*/

return [

    // Contextual variants that need dark text for accessible contrast on their tint.
    'dark_text_variants' => ['warning', 'light', 'info'],

    'fallback_variant' => 'secondary',

    // Priority badges (visits, requests, notifications, blood, etc.)
    'priority' => [
        'ROUTINE' => 'secondary',
        'LOW' => 'secondary',
        'NORMAL' => 'info',
        'STANDARD' => 'info',
        'URGENT' => 'warning',
        'HIGH' => 'warning',
        'EMERGENCY' => 'danger',
        'CRITICAL' => 'danger',
        'MASSIVE_TRANSFUSION' => 'danger',
    ],

    /*
    | Status → variant maps, keyed by domain. <x-status-badge :domain="..."/>
    | falls back to the `default` map, then to `fallback_variant`.
    */
    'status' => [

        // Generic workflow vocabulary — used when no domain is supplied.
        'default' => [
            'PENDING' => 'warning',
            'IN_PROGRESS' => 'info',
            'ACTIVE' => 'info',
            'OPEN' => 'info',
            'ON_HOLD' => 'warning',
            'HELD' => 'warning',
            'COMPLETED' => 'success',
            'DONE' => 'success',
            'APPROVED' => 'success',
            'VERIFIED' => 'success',
            'PASSED' => 'success',
            'REQUESTED' => 'secondary',
            'ACCEPTED' => 'info',
            'REJECTED' => 'danger',
            'FAILED' => 'danger',
            'OVERDUE' => 'danger',
            'CANCELLED' => 'secondary',
            'INACTIVE' => 'secondary',
            'CLOSED' => 'secondary',
            'INCONCLUSIVE' => 'warning',
        ],

        // Visit lifecycle
        'visit' => [
            'REGISTERED' => 'secondary',
            'WAITING_TRIAGE' => 'warning',
            'WAITING_CONSULTATION' => 'warning',
            'CONSULTING' => 'info',
            'IN_CONSULTATION' => 'info',
            'EMERGENCY' => 'danger',
            'ADMITTED' => 'primary',
            'COMPLETED' => 'success',
            'CANCELLED' => 'secondary',
        ],

        // Invoices — mirrors App\Enums\InvoiceStatus::color() (enum is the runtime
        // source of truth; this map is the fallback for plain-string statuses).
        'invoice' => [
            'DRAFT' => 'secondary',
            'UNPAID' => 'danger',
            'PENDING' => 'warning',
            'PARTIALLY_PAID' => 'info',
            'PAID' => 'success',
            'CANCELLED' => 'danger',
            'REFUNDED' => 'dark',
        ],

        // Payments
        'payment' => [
            'PENDING' => 'warning',
            'COMPLETED' => 'success',
            'SUCCESS' => 'success',
            'REVERSED' => 'secondary',
            'FAILED' => 'danger',
        ],

        // Medication Administration Record (dose / schedule status)
        'mar' => [
            'SCHEDULED' => 'secondary',
            'DUE' => 'info',
            'OVERDUE' => 'danger',
            'GIVEN' => 'success',
            'PARTIALLY_GIVEN' => 'success',
            'ADMINISTERED' => 'success',
            'COMPLETED' => 'success',
            'HELD' => 'warning',
            'MISSED' => 'danger',
            'REFUSED' => 'warning',
            'NOT_GIVEN' => 'danger',
            'SKIPPED' => 'secondary',
            'CORRECTED' => 'primary',
            'CANCELLED' => 'secondary',
            'VOIDED' => 'dark',
        ],

        // Medication order lifecycle (distinct from dose status above)
        'med_order' => [
            'PENDING' => 'warning',
            'ACTIVE' => 'info',
            'ACTIVE_ADMINISTRATION' => 'success',
            'HELD' => 'warning',
            'ON_HOLD' => 'warning',
            'STOPPED' => 'danger',
            'DISCONTINUED' => 'secondary',
            'COMPLETED' => 'success',
            'CANCELLED' => 'secondary',
        ],

        // Stock health
        'stock' => [
            'OK' => 'success',
            'IN_STOCK' => 'success',
            'LOW' => 'warning',
            'CRITICAL' => 'danger',
            'OUT' => 'danger',
            'OUT_OF_STOCK' => 'danger',
            'NOT_STOCKED' => 'secondary',
        ],

        // Stock requisition / movement workflow
        'requisition' => [
            'DRAFT' => 'secondary',
            'REQUESTED' => 'warning',
            'SUBMITTED' => 'warning',
            'APPROVED' => 'info',
            'ISSUED' => 'primary',
            'RECEIVED' => 'success',
            'ACKNOWLEDGED' => 'success',
            'REJECTED' => 'danger',
            'CANCELLED' => 'secondary',
        ],

        // Emergency case (emergency_status column)
        'emergency' => [
            'WAITING' => 'warning',
            'IN_TRIAGE' => 'warning',
            'UNDER_CARE' => 'info',
            'UNDER_EMERGENCY_CARE' => 'info',
            'ADMITTED' => 'primary',
            'DISPOSED' => 'secondary',
            'DISCHARGED' => 'success',
            'CANCELLED' => 'secondary',
        ],

        // Triage categories (do NOT recolour these — clinically standardised)
        'triage' => [
            'RED' => 'danger',
            'ORANGE' => 'warning',
            'YELLOW' => 'warning',
            'GREEN' => 'success',
            'BLUE' => 'info',
            'BLACK' => 'dark',
        ],

        // Emergency disposition
        'disposition' => [
            'ADMITTED' => 'primary',
            'DISCHARGED' => 'success',
            'TRANSFERRED_TO_OPD' => 'info',
            'TRANSFERRED_TO_THEATRE' => 'info',
            'REFERRED_OUT' => 'warning',
            'LEFT_AGAINST_MEDICAL_ADVICE' => 'warning',
            'ABSCONDED' => 'warning',
            'DIED' => 'dark',
            'DEAD_ON_ARRIVAL' => 'dark',
        ],

        // Theatre / procedure
        'theatre' => [
            'REQUESTED' => 'secondary',
            'ACCEPTED' => 'info',
            'SCHEDULED' => 'info',
            'IN_PROGRESS' => 'primary',
            'COMPLETED' => 'success',
            'POSTPONED' => 'warning',
            'CANCELLED' => 'secondary',
            'REJECTED' => 'danger',
        ],

        // Investigations / lab
        'lab' => [
            'REQUESTED' => 'secondary',
            'ACCEPTED' => 'info',
            'IN_PROGRESS' => 'info',
            'RESULT_ENTERED' => 'warning',
            'VERIFIED' => 'success',
            'COMPLETED' => 'success',
            'REJECTED' => 'danger',
            'CANCELLED' => 'secondary',
        ],

        // ── Blood bank ──
        'blood_unit' => [
            'COLLECTED' => 'secondary',
            'QUARANTINED' => 'warning',
            'SCREENING_PENDING' => 'warning',
            'AVAILABLE' => 'success',
            'RESERVED' => 'info',
            'CROSSMATCHED' => 'info',
            'ISSUED' => 'primary',
            'TRANSFUSED' => 'success',
            'EXPIRED' => 'secondary',
            'DISCARDED' => 'secondary',
            'REJECTED' => 'danger',
        ],
        'blood_request' => [
            'PENDING' => 'warning',
            'APPROVED' => 'info',
            'PARTIALLY_ISSUED' => 'primary',
            'ISSUED' => 'primary',
            'COMPLETED' => 'success',
            'CANCELLED' => 'secondary',
        ],
        'blood_issue' => [
            'ISSUED' => 'primary',
            'TRANSFUSED' => 'success',
            'REACTION_RECORDED' => 'warning',
            'RETURNED' => 'secondary',
            'VOIDED' => 'secondary',
        ],
        'crossmatch' => [
            'PENDING' => 'warning',
            'COMPATIBLE' => 'success',
            'COMPATIBLE_WITH_CAUTION' => 'warning',
            'INCOMPATIBLE' => 'danger',
            'EMERGENCY_OVERRIDE' => 'dark',
            'CANCELLED' => 'secondary',
        ],
        'screening' => [
            'PENDING' => 'warning',
            'PASSED' => 'success',
            'FAILED' => 'danger',
            'INCONCLUSIVE' => 'warning',
            'NOT_DONE' => 'secondary',
            'NEGATIVE' => 'success',
            'NON_REACTIVE' => 'success',
            'POSITIVE' => 'danger',
            'REACTIVE' => 'danger',
        ],
        'donor_screening' => [
            'REGISTERED' => 'secondary',
            'QUESTIONNAIRE_PENDING' => 'warning',
            'PHYSICAL_ASSESSMENT_PENDING' => 'warning',
            'ELIGIBLE' => 'success',
            'NEEDS_REVIEW' => 'warning',
            'TEMPORARILY_DEFERRED' => 'warning',
            'PERMANENTLY_DEFERRED' => 'danger',
        ],

        // Claims
        'claim' => [
            'DRAFT' => 'secondary',
            'PREPARED' => 'secondary',
            'READY' => 'info',
            'SUBMITTED' => 'info',
            'APPROVED' => 'success',
            'PAID' => 'success',
            'PARTIALLY_PAID' => 'warning',
            'REJECTED' => 'danger',
            'CANCELLED' => 'secondary',
        ],
    ],
];
