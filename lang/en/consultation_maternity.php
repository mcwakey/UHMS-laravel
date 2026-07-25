<?php

return [
    // Phase 14R.2 — Consultation ↔ Maternity bridge (domain/service messages).
    'maternity_context' => 'Maternity Context',

    'context_types' => [
        'pregnancy_profile' => 'Pregnancy Profile',
        'maternity_case' => 'Maternity Case',
        'anc_visit' => 'ANC Visit',
        'labor' => 'Labor Episode',
        'delivery' => 'Delivery Record',
        'newborn' => 'Newborn Record',
        'postnatal' => 'Postnatal Case',
    ],

    'link_roles' => [
        'primary' => 'Primary',
        'reviewed' => 'Reviewed',
        'created' => 'Created',
        'handoff' => 'Handoff',
        'historical' => 'Historical',
    ],

    'resolution_sources' => [
        'explicit' => 'Explicit link',
        'visit' => 'Same visit context',
        'admission' => 'Same admission context',
        'active_profile' => 'Active pregnancy context',
        'none' => 'No maternity context',
    ],

    'statuses' => [
        'resolved' => 'Maternity context resolved',
        'ambiguous' => 'Ambiguous maternity context',
        'none' => 'No maternity context',
        'invalid' => 'Invalid maternity context',
    ],

    'context_linked' => 'Maternity context linked.',
    'context_relinked' => 'Maternity context relinked.',
    'context_unlinked' => 'Maternity context unlinked.',

    'link_reason' => 'Reason for linking',
    'unlink_reason' => 'Reason for unlinking',

    'no_maternity_context' => 'No maternity context is linked to this consultation.',
    'ambiguous_maternity_context' => 'The maternity context is ambiguous and must be selected explicitly.',
    'multiple_active_pregnancy_profiles' => 'This patient has more than one active pregnancy profile.',

    'errors' => [
        'unsupported_target' => 'Unsupported maternity target: :target.',
        'invalid_target' => 'The maternity record could not be validated.',
        'patient_mismatch' => 'The maternity record belongs to a different patient.',
        'inconsistent_context' => 'The maternity record chain is inconsistent and cannot be linked.',
        'relink_required' => 'A different record is already linked for this context. Use relink with a reason.',
        'reason_required' => 'A reason is required.',
        'link_not_found' => 'No active maternity link was found for this context.',
    ],

    // ── Phase 14R.3 — Obstetrics stage-aware workspace ────────────────────
    'dating_methods' => [
        'lmp' => 'LMP',
        'early_ultrasound' => 'Early ultrasound',
        'late_ultrasound' => 'Late ultrasound',
        'assisted_reproduction' => 'Assisted reproduction',
        'clinical_estimate' => 'Clinical estimate',
        'unknown' => 'Unknown',
    ],

    'ribbon' => [
        'title' => 'Maternity Context',
        'explicitly_linked' => 'Explicitly linked',
        'suggested' => 'Suggested Maternity Context',
        'source_maternity' => 'Source: Maternity',
        'source_same_visit' => 'Source: same visit',
        'source_same_admission' => 'Source: same admission',
        'source_active_profile' => 'Source: active pregnancy profile',
        'gestational_age' => 'Gestational age',
        'gestational_age_source' => 'GA source',
        'dating_method' => 'Dating method',
        'edd' => 'EDD',
        'risk_level' => 'Risk level',
        'latest_anc' => 'Latest ANC',
        'next_anc' => 'Next ANC',
        'admission' => 'Admission',
        'ward_bed' => 'Ward / Bed',
        'labor_stage' => 'Labor stage',
        'delivery_status' => 'Delivery',
        'newborn_records' => 'Newborn records',
        'postnatal_status' => 'Postnatal',
        'workspace_disabled' => 'Maternity workspace integration is disabled.',
        'pilot_mode' => 'Pilot mode — projections shown, existing fields remain editable.',
    ],

    'panel' => [
        'title' => 'Maternity Context',
        'pregnancy' => 'Pregnancy',
        'anc' => 'ANC',
        'labor_delivery' => 'Labor and Delivery',
        'newborn' => 'Newborn',
        'postnatal' => 'Postnatal',
        'pregnancy_summary' => 'Pregnancy summary',
        'anc_summary' => 'ANC summary',
        'labor_summary' => 'Labor summary',
        'delivery_summary' => 'Delivery summary',
        'newborn_summary' => 'Newborn summary',
        'postnatal_summary' => 'Postnatal summary',
        'legacy_entry' => 'Legacy Consultation Entry',
        'legacy_entry_hint' => 'Recorded before maternity became the source of truth. Kept for audit; not used as current clinical truth.',
        'read_only_reason' => 'Read-only because Maternity is the source of truth.',
    ],

    'actions' => [
        'confirm_and_link' => 'Confirm and Link',
        'select_profile' => 'Select pregnancy profile',
        'link_profile' => 'Link pregnancy profile',
        'create_profile' => 'Create pregnancy profile',
        'link_or_create_profile' => 'Link or Create Pregnancy Profile',
        'relink_profile' => 'Relink pregnancy profile',
        'unlink_profile' => 'Unlink pregnancy profile',
        'record_anc' => 'Record ANC Visit',
        'start_labor' => 'Start Labor Episode',
        'open_labor' => 'Open Labor Workspace',
        'open_maternity' => 'Open Maternity Workspace',
        'view_delivery' => 'View Delivery Record',
        'view_newborn' => 'View Newborn Records',
        'view_postnatal' => 'Open Postnatal Workspace',
    ],

    'messages' => [
        'profile_created' => 'Pregnancy profile created and linked to this consultation.',
        'profile_linked' => 'Pregnancy profile linked to this consultation.',
        'context_confirmed' => 'Maternity context confirmed and linked.',
        'anc_recorded' => 'ANC visit recorded and linked to this consultation.',
        'labor_started' => 'Labor episode started and linked to this consultation.',
        'labor_opened' => 'An active labor episode already exists.',
        'explicit_link_required' => 'Confirm the maternity context before recording maternity data.',
        'maternity_permission_required' => 'You also need the underlying maternity permission for this action.',
        'workspace_disabled' => 'The maternity workspace integration is not enabled.',
        'invalid_context_actions_disabled' => 'Maternity actions are disabled until the invalid context is corrected.',
    ],

    'write_guard' => [
        'field_blocked' => ':field is maintained in the maternity record and cannot be saved here.',
        'section_read_only' => 'These values are read-only because Maternity is the source of truth.',
        'guard_disabled' => 'Pilot mode: maternity fields remain editable in this consultation.',
    ],

    'warnings' => [
        'multiple_active_profiles' => 'This patient has more than one active pregnancy profile. Select one explicitly.',
        'multiple_candidate_profiles' => 'More than one pregnancy profile matches this context. Select one explicitly.',
        'link_target_missing' => 'The linked :context record is missing.',
        'link_patient_mismatch' => 'The linked :context record belongs to a different patient.',
        'source_of_truth' => 'Maternity records are the source of truth for this information.',
        'duplicate_data' => 'This information is already recorded in the maternity record.',
    ],
];
