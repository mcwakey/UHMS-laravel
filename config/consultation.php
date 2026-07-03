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
];
