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
        'historical_label' => 'These are the values recorded at consultation completion, not current Maternity data.',
        'abortions' => 'Abortions',
        'living_children' => 'Living children',
        'blood_pressure' => 'Blood pressure',
        'weight' => 'Weight',
        'fundal_height' => 'Fundal height',
        'fetal_heart_rate' => 'Fetal heart rate',
        'presentation' => 'Presentation',
        'danger_signs' => 'Danger signs',
        'risk_flags' => 'Risk flags',
        'latest_observation' => 'Latest observation',
        'cervical_dilation' => 'Cervical dilation',
        'theatre_escalation' => 'Theatre escalation flagged',
        'maternal_condition' => 'Maternal condition',
        'estimated_blood_loss' => 'Estimated blood loss',
        'ready_for_discharge' => 'Ready for discharge',
        'follow_up_date' => 'Follow-up date',
        'latest_mother_observation' => 'Latest mother observation',
        'latest_newborn_observation' => 'Latest newborn observation',
        'sex' => 'Sex',
        'apgar' => 'Apgar 1/5/10',
        'resuscitation' => 'Resuscitation',
        'ward' => 'Ward',
        'bed' => 'Bed',
        'operational_owner_admission' => 'Operational owner: Admission',
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


    'snapshot' => [
        'verified' => 'Verified against the hash stored at capture',
        'verified_short' => 'Verified',
        'mismatch_short' => 'Mismatch',
        'hash_mismatch' => 'Snapshot integrity check failed',
        'tamper_evidence_warning' => 'The stored payload no longer matches the hash recorded when it was captured. This is tamper evidence only — it is not an external signature and does not by itself prove non-repudiation.',
        'verification_unavailable' => 'Integrity verification is unavailable for this snapshot.',
        'cannot_be_edited' => 'Snapshots cannot be edited.',
        'cannot_be_deleted' => 'Snapshots cannot be deleted.',
        'historical_versions' => 'Historical versions',
        'latest' => 'Latest',
        'previous' => 'Previous snapshot',
        'next' => 'Next snapshot',
        'integrity_verification' => 'Integrity',
        'none_fabricated' => 'No historical snapshot has been fabricated.',
        'completed_before_capture_enabled' => 'This consultation may have been completed before snapshot capture was enabled.',
    ],

    'current' => [
        'view' => 'View current Maternity record',
        'hide' => 'Hide current Maternity record',
        'not_part_of_snapshot' => 'Not part of the completion-time snapshot',
        'may_differ' => 'Current values may differ from the historical snapshot.',
        'loaded_separately' => 'The current record was loaded separately and does not change the snapshot.',
        'unavailable' => 'No explicit current Maternity context is available.',
        'permission_required' => 'The underlying Maternity permission is required.',
    ],

    'reopened' => [
        'title' => 'Consultation reopened',
        'live_values_shown' => 'Live values are shown while the consultation is active.',
        'recompletion_creates_version' => 'Recompletion will create a new snapshot version.',
        'previous_snapshots' => 'Previous completion snapshots',
    ],

    'print' => [
        'historical_summary' => 'Historical Consultation Summary',
        'printed_snapshot_version' => 'Printed snapshot version',
        'current_record_print' => 'Current-record print',
    ],

];
