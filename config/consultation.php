<?php

return [
    'prescriptions' => [
        'require_diagnosis_before_prescribing' => false,
        'allow_missing_diagnosis_override' => false,
        'duplicate_active_medication_days' => 30,
    ],

    'completion_checklist' => [
        'enabled' => true,
        'requirements' => [
            'complaint',
            'examination',
            'diagnosis',
            'plan_or_disposition',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Consultation ↔ Maternity context (Phase 14R.3)
    |--------------------------------------------------------------------------
    |
    | Two independent, reversible flags. BOTH default to false, so the
    | Obstetrics workspace behaves exactly as it does today until a deployment
    | opts in.
    |
    |   obstetrics_workspace_enabled
    |       Renders the maternity context ribbon and read-only projections.
    |       Existing specialty fields stay editable (safe pilot mode).
    |
    |   obstetrics_write_guard_enabled
    |       Blocks NEW writes to maternity-owned specialty fields when an
    |       explicit pregnancy-profile link exists. Historical entries are
    |       never deleted or rewritten.
    |
    | The write guard is inert unless the workspace flag is also enabled —
    | enforced in code, not merely by convention.
    |
    */
    'maternity_context' => [
        'obstetrics_workspace_enabled' => env(
            'CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED',
            false
        ),

        'obstetrics_write_guard_enabled' => env(
            'CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED',
            false
        ),

        /*
        | Phase 14R.4 — Gynaecology. Fully independent of the Obstetrics pair
        | above, and both default to false.
        |
        |   gynaecology_context_enabled
        |       Allows a small pregnancy-context card when a profile is
        |       EXPLICITLY linked. Gynaecology never infers context from the
        |       visit, admission or a single active profile.
        |
        |   gynaecology_write_guard_enabled
        |       Projects obstetric-history fields from the linked pregnancy
        |       profile instead of allowing consultation writes. Inert unless
        |       the context flag is also on.
        */
        'gynaecology_context_enabled' => env(
            'CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED',
            false
        ),

        'gynaecology_write_guard_enabled' => env(
            'CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED',
            false
        ),
    ],
];
