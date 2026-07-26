<?php

/*
 * Phase 14R.5 — Consultation / Emergency / Admission / Maternity handoffs.
 * Keep strict recursive key parity with lang/fr/maternity_handoffs.php.
 */

return [

    'ownership' => [
        'operational_owner' => 'Operational owner',
        'consultation_encounter' => 'Consultation encounter',
        'emergency_episode' => 'Emergency episode',
        'admission_episode' => 'Admission episode',
        'maternity_longitudinal_record' => 'Maternity longitudinal record',
        'source_of_truth' => 'Source of truth',
        'handoff_context' => 'Handoff context',
    ],

    'cards' => [
        'pregnancy' => 'Pregnancy Profile',
        'anc' => 'ANC Visit',
        'labor' => 'Labor Episode',
        'delivery' => 'Delivery Record',
        'newborn' => 'Newborn Record',
        'postnatal' => 'Postnatal Case',
        'record_ref' => ':type #:id',
        'record_count' => ':count newborn record(s)',
        'open_record' => 'Open record',
        'no_context' => 'No maternity context is linked to this record.',
        'title' => 'Maternity context',
    ],

    'fields' => [
        'status' => 'Status',
        'gestational_age' => 'Gestational age',
        'edd' => 'EDD',
        'dating_method' => 'Dating method',
        'risk' => 'Risk',
        'latest_visit' => 'Latest visit',
        'visit_number' => 'Visit number',
        'next_visit' => 'Next visit',
        'stage' => 'Stage',
        'started_at' => 'Started',
        'escalation' => 'Escalation',
        'emergency_escalation_flagged' => 'Emergency escalation flagged',
        'delivered_at' => 'Delivered',
        'mode' => 'Mode',
        'outcome' => 'Outcome',
        'newborn_count' => 'Newborns',
        'birth_weights' => 'Birth weights',
        'apgar_5' => 'Apgar (5 min)',
        'mother_ready' => 'Mother ready',
        'newborn_ready' => 'Newborn ready',
        'not_ready' => 'Not ready',
        'referral' => 'Referral',
        'referral_required' => 'Referral required',
    ],

    'risks' => [
        'previous_caesarean' => 'Previous caesarean',
        'previous_pph' => 'Previous PPH',
        'hypertensive' => 'Hypertensive disorder risk',
        'diabetes' => 'Diabetes risk',
        'multiple' => 'Multiple pregnancy',
    ],

    'consultation' => [
        'title' => 'Maternity handoffs',
        'create_admission_request' => 'Create Admission Request',
        'existing_admission_request' => 'Existing Admission Request',
        'admission_request_from_consultation' => 'Admission Request created from Consultation',
        'refer_obstetrics' => 'Refer to Obstetrics/Maternity',
        'remains_gynaecology' => 'This consultation remains Gynaecology.',
        'open_obstetrics_referral' => 'Open Obstetrics referral',
        'open_postnatal_review' => 'Open Postnatal review',
        'explicit_link_required' => 'Link a Pregnancy Profile before creating an Admission Request.',
        'no_billing_posted' => 'No billing is posted and no admission is created by this action.',
    ],

    'emergency' => [
        'title' => 'Emergency Maternity Context',
        'link_pregnancy_profile' => 'Link Pregnancy Profile',
        'create_pregnancy_profile' => 'Create Pregnancy Profile',
        'start_or_open_labor' => 'Start/Open Labor',
        'create_or_open_admission_request' => 'Create/Open Admission Request',
        'emergency_owns_acute_care' => 'Emergency remains owner of acute care.',
        'maternity_owns_pregnancy' => 'Maternity owns pregnancy and labor.',
        'suggested_context' => 'Suggested context',
        'suggested_context_notice' => 'A maternity record exists on this visit. It is shown as a suggestion only and has not been linked.',
        'confirm_link' => 'Confirm link',
        'ambiguous_profile' => 'Ambiguous Pregnancy Profile — choose one explicitly.',
        'no_context_notice' => 'No Pregnancy Profile is linked. Nothing has been created automatically.',
    ],

    'admission' => [
        'title' => 'Admission Maternity Context',
        'context_from_request' => 'Context carried from Admission Request',
        'context_linked_directly' => 'Context linked directly',
        'open_pregnancy_profile' => 'Open Pregnancy Profile',
        'open_labor' => 'Open Labor',
        'open_delivery' => 'Open Delivery',
        'open_newborn' => 'Open Newborn',
        'open_postnatal' => 'Open Postnatal',
        'context_conflict' => 'Maternity context conflict — review required.',
        'ambiguous_legacy_source' => 'This request has a legacy maternity source whose record type cannot be determined. Link the correct context explicitly.',
        'admission_owns' => 'Bed, ward, nursing, medication and discharge remain Admission-owned.',
        'clinical_writes_in_maternity' => 'Clinical records are entered in the Maternity workspace.',
    ],

    'handoffs' => [
        'created' => 'Handoff created',
        'already_exists' => 'Handoff already exists',
        'unavailable' => 'Handoff unavailable',
        'blocked' => 'Handoff blocked',
        'record_reused' => 'Record reused',
        'duplicate_prevented' => 'Duplicate prevented',
        'context_propagation_complete' => 'Context propagation complete',
        'context_propagation_conflict' => 'Context propagation conflict',
        'return_to_consultation' => 'Return to Consultation',
        'return_to_emergency' => 'Return to Emergency',
        'return_to_admission' => 'Return to Admission',
        'return_to_maternity' => 'Return to Maternity',
    ],

    'postnatal' => [
        'review' => 'Postnatal review',
        'readiness_advisory' => 'Postnatal readiness is advisory.',
        'record_observations_in_maternity' => 'Record observations in Maternity.',
        'no_observations_duplicated' => 'No observations were duplicated.',
        'link_case' => 'Link Postnatal Case',
    ],

    'messages' => [
        'context_linked' => 'Maternity context linked.',
        'context_relinked' => 'Maternity context replaced. The previous link was kept as history.',
        'context_unlinked' => 'Maternity context unlinked. The previous link was kept as history.',
        'profile_created' => 'Pregnancy Profile #:id created and linked.',
        'labor_started' => 'Labor Episode #:id started.',
        'labor_reused' => 'Labor Episode #:id is already active and was reused.',
        'admission_request_created' => 'Admission Request #:id created.',
        'admission_request_exists' => 'Admission Request #:id is already open and was reused.',
        'emergency_case_created' => 'Emergency Case #:id created.',
        'emergency_case_exists' => 'Emergency Case #:id is already active and was reused.',
        'obstetrics_referral_created' => 'Obstetrics referral created. This consultation remains Gynaecology.',
        'obstetrics_referral_exists' => 'An Obstetrics referral already exists for this visit.',
        'obstetrics_referral_unavailable' => 'No Obstetrics consultation department is configured. Use the standard create-consultation flow.',
        'postnatal_review_linked' => 'Postnatal Case linked for review. Observations are recorded in Maternity.',
        'handoff_unavailable' => 'This handoff is not available in this environment.',
    ],

];
