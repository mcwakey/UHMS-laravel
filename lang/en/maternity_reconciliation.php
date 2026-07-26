<?php

/* Phase 14R.6 — dry-run O&G reconciliation report. Strict parity with fr/. */

return [
    'dry_run_only' => 'Dry-run only — no database changes were made.',
    'apply_mode_unavailable' => 'Apply mode is not available in Phase 14R.6.',
    'no_database_changes' => 'No database changes were made.',
    'classification' => 'Classification',
    'count' => 'Count',
    'parser_warning' => 'Parser warning',
    'recommended_action' => 'Recommended action',
    'original_entry_preserved' => 'The original specialty entry is preserved.',
    'report_written' => 'Report written to :path',

    'classifications' => [
        'safe_to_link' => 'Safe to link',
        'safe_to_migrate' => 'Safe to migrate',
        'conflict_requires_review' => 'Conflict requires review',
        'historical_only' => 'Historical only',
        'insufficient_context' => 'Insufficient context',
    ],

    'actions' => [
        'create_bridge_link_only' => 'Create a bridge link only',
        'create_target_record_then_link' => 'Create the target record, then link',
        'review_manually' => 'Review manually',
        'preserve_as_encounter_history' => 'Preserve as encounter history',
    ],
];
