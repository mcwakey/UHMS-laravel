<?php

/*
 * Phase 14R.6 — advisory readiness, summary projection and completion
 * snapshots. Keep strict recursive parity with the fr/ file.
 */

return [

    'readiness' => [
        'title' => 'Maternity readiness',
        'advisory_notice' => 'Advisory only — completion is still allowed.',
        'record_in_maternity' => 'Record the missing information in Maternity.',
        'statuses' => [
            'ready' => 'Ready',
            'warning' => 'Advisory warning',
            'unavailable' => 'Unavailable',
        ],
        'modes' => [
            'general' => 'General review',
            'antenatal_review' => 'Antenatal review',
            'labor_review' => 'Labor review',
            'postnatal_review' => 'Postnatal review',
        ],
        'warnings' => [
            'context_confirmation_required' => 'Confirm and link this Maternity context first.',
            'context_ambiguous' => 'Several Pregnancy Profiles match — choose one explicitly.',
            'context_invalid' => 'The linked Maternity context is invalid.',
            'pregnancy_profile_missing' => 'No Pregnancy Profile is linked.',
            'anc_visit_not_recorded' => 'No ANC Visit is linked or recorded.',
            'labor_episode_missing' => 'No Labor Episode is linked.',
            'labor_episode_profile_mismatch' => 'The linked Labor Episode no longer matches the Pregnancy Profile.',
            'postnatal_case_missing' => 'No Postnatal Case is linked.',
            'postnatal_referral_required' => 'The Postnatal Case is flagged for referral.',
            'postnatal_readiness_unavailable' => 'Postnatal readiness is not yet recorded.',
        ],
    ],

    'summary' => [
        'section_title' => 'Maternity Context',
        'current_record' => 'Current Maternity Record',
        'completion_snapshot_title' => 'Maternity Context at Consultation Completion',
        'completion_snapshot' => 'Completion Snapshot',
        'snapshot_version' => 'Snapshot version',
        'captured_at' => 'Captured at',
        'captured_by' => 'Captured by',
        'snapshot_hash' => 'Snapshot hash',
        'hash_verified' => 'Hash verified',
        'hash_mismatch' => 'Hash mismatch — this record was altered after capture',
        'no_snapshot_available' => 'No Maternity completion snapshot was captured for this Consultation.',
        'view_current_record' => 'View current Maternity record',
        'historical_snapshots' => 'Historical completion snapshots',
        'source_of_truth' => 'Source of truth: Maternity',
        'encounter_source' => 'Encounter source: Consultation',
        'admission_owns_ward' => 'Ward and bed are owned by Admission.',
        'sections' => [
            'pregnancy' => 'Pregnancy',
            'anc' => 'Antenatal',
            'labor' => 'Labor',
            'delivery' => 'Delivery',
            'newborn' => 'Newborn',
            'postnatal' => 'Postnatal',
            'admission' => 'Admission context',
        ],
    ],

    'warnings' => [
        'confirm_context_first' => 'Confirm and link this Maternity context first.',
        'context_ambiguous' => 'Several Pregnancy Profiles match — choose one explicitly.',
        'context_invalid' => 'The linked Maternity context is invalid.',
        'context_warnings' => 'The linked Maternity context reported warnings.',
    ],

];
