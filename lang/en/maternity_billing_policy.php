<?php

/* Phase 14R.6 — billing de-duplication policy (advisory). Parity with fr/. */

return [
    'title' => 'Billing de-duplication policy',
    'posting_unavailable' => 'Posting remains unavailable.',
    'base_encounter_charge' => 'Base encounter charge',
    'event_specific_consultation_charge' => 'Event-specific Consultation charge',
    'maternity_event_charge' => 'Maternity event charge',
    'consultation_source_suppressed' => 'Consultation source suppressed',
    'maternity_source_suppressed' => 'Maternity source suppressed',

    'policies' => [
        'consultation_only' => 'Consultation only',
        'maternity_event_only' => 'Maternity event only',
        'both_when_configured' => 'Both when configured',
        'manual_selection' => 'Manual selection',
    ],

    'statuses' => [
        'policy_disabled' => 'Billing policy disabled',
        'no_conflict' => 'No conflict',
        'duplicate_source_suppressed' => 'Duplicate source suppressed',
        'both_disabled' => 'Both sources disabled',
        'manual_review_required' => 'Manual review required',
    ],

    'warnings' => [
        'event_specific_duplicate_risk' => 'Event-specific duplicate risk',
        'both_disabled' => 'Both-source billing is configured but globally disabled.',
        'manual_selection_disabled' => 'Manual selection is required but globally disabled.',
        'missing_source_identity' => 'The source record has no reliable billing identity.',
        'ambiguous_legacy_source' => 'The legacy Maternity source cannot be interpreted automatically.',
    ],
];
