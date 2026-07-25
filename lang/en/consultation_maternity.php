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

    'warnings' => [
        'multiple_active_profiles' => 'This patient has more than one active pregnancy profile. Select one explicitly.',
        'multiple_candidate_profiles' => 'More than one pregnancy profile matches this context. Select one explicitly.',
        'link_target_missing' => 'The linked :context record is missing.',
        'link_patient_mismatch' => 'The linked :context record belongs to a different patient.',
        'source_of_truth' => 'Maternity records are the source of truth for this information.',
        'duplicate_data' => 'This information is already recorded in the maternity record.',
    ],
];
