<?php

return [
    'safety' => [
        'prescription_warning' => 'Prescription safety warning',
        'override_required' => 'Review the prescription warnings and enter an override reason to continue.',
        'override_reason' => 'Override reason',
        'allergy_conflict' => 'This prescription may conflict with a recorded patient allergy.',
        'duplicate_active_medication' => 'This patient already has an active or recent prescription for this medication.',
        'missing_diagnosis' => 'Add a diagnosis before prescribing.',
        'unusual_dose_unverified' => 'The dose could not be verified. Please review it before continuing.',
        'missing_required_field' => 'Complete all required prescription fields before continuing.',
    ],
    'completion' => [
        'ready' => 'Ready for completion',
        'not_ready' => 'Not ready for completion',
        'missing_requirements' => 'Complete the required consultation items before closing this session.',
        'blocked' => 'Consultation completion blocked until required clinical items are recorded.',
        'override_reason' => 'Completion override reason',
        'readiness_title' => 'Completion readiness',
        'requirement' => [
            'complaint' => 'Presenting complaint or reason for visit',
            'examination' => 'Examination findings',
            'diagnosis' => 'Diagnosis',
            'plan_or_disposition' => 'Treatment plan or disposition note',
        ],
    ],
];
