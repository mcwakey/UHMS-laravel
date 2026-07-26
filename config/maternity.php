<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Phase 14R.5 — operational handoff integration
    |--------------------------------------------------------------------------
    |
    | Four independent, reversible flags. ALL default to false, so Consultation,
    | Emergency, Admission and Maternity behave exactly as they do today until a
    | deployment opts in.
    |
    | Context flags are READ-ONLY surfaces. Handoff flags add explicit clinician
    | actions. A context flag never creates a record; a handoff flag never fires
    | automatically from a warning, diagnosis, risk flag or context match.
    |
    |   consultation_handoffs_enabled
    |       Consultation → Admission Request, Gynaecology → Obstetrics referral
    |       and Postnatal review linking.
    |
    |   emergency_context_enabled
    |       Read-only Maternity context card in the Emergency case workspace.
    |
    |   admission_context_enabled
    |       Read-only Maternity context card in the Admission workspace, plus
    |       request→admission context propagation on conversion.
    |
    |   emergency_handoffs_enabled
    |       Explicit Emergency → Maternity actions (link/create profile, start or
    |       reuse Labor, create or reuse Admission Request) AND explicit
    |       Maternity → Emergency escalation handoffs.
    |
    | The four Obstetrics/Gynaecology flags in config/consultation.php remain
    | fully independent of these.
    |
    */
    'integration' => [
        'consultation_handoffs_enabled' => env(
            'MATERNITY_CONSULTATION_HANDOFFS_ENABLED',
            false
        ),

        'emergency_context_enabled' => env(
            'MATERNITY_EMERGENCY_CONTEXT_ENABLED',
            false
        ),

        'admission_context_enabled' => env(
            'MATERNITY_ADMISSION_CONTEXT_ENABLED',
            false
        ),

        'emergency_handoffs_enabled' => env(
            'MATERNITY_EMERGENCY_HANDOFFS_ENABLED',
            false
        ),
    ],

];
