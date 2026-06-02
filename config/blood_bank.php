<?php

/*
|--------------------------------------------------------------------------
| Blood Bank Configuration
|--------------------------------------------------------------------------
|
| WHO-inspired donor selection thresholds, infectious-disease screening
| tests, component expiry windows, and ABO/Rh component-specific
| compatibility matrices. Adapt every value to local hospital policy and
| confirm with the transfusion committee before production use.
|
| Compatibility matrices are expressed as "recipient ABO => list of donor
| ABO groups that may be given". They are read by BloodBankCompatibilityService
| so that the backend (never just the UI) enforces compatibility.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Donor eligibility thresholds
    |--------------------------------------------------------------------------
    */
    'donor' => [
        'minimum_age' => 18,
        'maximum_age' => 65,
        'minimum_weight_kg' => 50,
        'minimum_hb_male' => 13.0,
        'minimum_hb_female' => 12.5,
        'maximum_temperature_c' => 37.5,
        'pulse_min' => 50,
        'pulse_max' => 100,
        'systolic_min' => 90,
        'systolic_max' => 180,
        'diastolic_min' => 50,
        'diastolic_max' => 100,
        // Minimum days between successive whole-blood donations.
        'donation_interval_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Questionnaire risk answers that trigger a temporary deferral suggestion
    |--------------------------------------------------------------------------
    | These keys map to boolean answers captured on the donor questionnaire.
    | A "true" answer flags the matching deferral suggestion.
    */
    'questionnaire_risk' => [
        'temporary' => [
            'current_fever', 'recent_illness', 'recent_medication', 'recent_surgery',
            'recent_hospitalization', 'recent_tattoo_piercing', 'recent_transfusion',
            'recent_malaria', 'recent_travel_high_risk', 'currently_pregnant',
            'recently_delivered', 'breastfeeding', 'recent_miscarriage',
            'heavy_menstrual_bleeding', 'alcohol_before_donation', 'history_fainting',
        ],
        'permanent' => [
            'hiv_risk_exposure', 'sti_history', 'drug_injection_history',
            'high_risk_sexual_exposure', 'jaundice_hepatitis_history',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Component expiry windows (days from collection)
    |--------------------------------------------------------------------------
    */
    'component_expiry_days' => [
        'WHOLE_BLOOD' => 35,
        'PRBC' => 42,
        'PACKED_RED_CELLS' => 42,
        'RED_CELLS' => 42,
        'PLATELETS' => 5,
        'PLASMA' => 365,
        'FRESH_FROZEN_PLASMA' => 365,
        'FFP' => 365,
        'CRYOPRECIPITATE' => 365,
    ],
    'default_expiry_days' => 35,

    /*
    |--------------------------------------------------------------------------
    | Infectious-disease / laboratory screening tests
    |--------------------------------------------------------------------------
    | mandatory => a reactive/positive result forces unit rejection and a
    | non-reactive/negative result is required before the unit can pass.
    */
    'screening_tests' => [
        'HIV' => ['label' => 'HIV', 'mandatory' => true],
        'HBV' => ['label' => 'Hepatitis B (HBsAg)', 'mandatory' => true],
        'HCV' => ['label' => 'Hepatitis C', 'mandatory' => true],
        'SYPHILIS' => ['label' => 'Syphilis (VDRL/TPHA)', 'mandatory' => true],
        'MALARIA' => ['label' => 'Malaria', 'mandatory' => true],
        'BLOOD_GROUPING' => ['label' => 'Blood Grouping', 'mandatory' => false],
        'RH_TYPING' => ['label' => 'Rh Typing', 'mandatory' => false],
    ],

    // When true, every mandatory screening test must be verified by a second
    // user before the unit can be approved/made available.
    'require_test_verification' => true,

    /*
    |--------------------------------------------------------------------------
    | Component → compatibility model
    |--------------------------------------------------------------------------
    */
    'component_compatibility_type' => [
        'WHOLE_BLOOD' => 'red_cell',
        'PRBC' => 'red_cell',
        'PACKED_RED_CELLS' => 'red_cell',
        'RED_CELLS' => 'red_cell',
        'PLASMA' => 'plasma',
        'FRESH_FROZEN_PLASMA' => 'plasma',
        'FFP' => 'plasma',
        'CRYOPRECIPITATE' => 'plasma',
        'PLATELETS' => 'platelet',
    ],
    'default_compatibility_type' => 'red_cell',

    /*
    |--------------------------------------------------------------------------
    | ABO compatibility matrices  (recipient ABO => allowed donor ABO groups)
    |--------------------------------------------------------------------------
    */
    'compatibility' => [
        // Red cells / whole blood — standard ABO.
        'red_cell' => [
            'O' => ['O'],
            'A' => ['A', 'O'],
            'B' => ['B', 'O'],
            'AB' => ['AB', 'A', 'B', 'O'],
        ],
        // Plasma — reverse of red cells (AB plasma is the universal donor).
        'plasma' => [
            'O' => ['O', 'A', 'B', 'AB'],
            'A' => ['A', 'AB'],
            'B' => ['B', 'AB'],
            'AB' => ['AB'],
        ],
        // Platelets — ABO-identical/compatible preferred (else compatible with caution).
        'platelet' => [
            'O' => ['O'],
            'A' => ['A', 'O'],
            'B' => ['B', 'O'],
            'AB' => ['AB', 'A', 'B', 'O'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rh handling
    |--------------------------------------------------------------------------
    */
    'rh' => [
        // Enforce Rh-negative recipients receive Rh-negative red cells.
        'enforce_for_red_cell' => true,
        // Rh is generally not enforced for plasma (no red cells present).
        'enforce_for_plasma' => false,
        // Platelets: Rh mismatch (Rh+ to Rh- recipient) is allowed with caution.
        'enforce_for_platelet' => false,
        // Allow an authorised emergency Rh-positive issue to an Rh-negative recipient.
        'allow_emergency_override' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Issue / crossmatch rules
    |--------------------------------------------------------------------------
    */
    'require_crossmatch_before_issue' => [
        'WHOLE_BLOOD' => true,
        'PRBC' => true,
        'PACKED_RED_CELLS' => true,
        'RED_CELLS' => true,
        'PLASMA' => false,
        'FRESH_FROZEN_PLASMA' => false,
        'FFP' => false,
        'PLATELETS' => false,
        'CRYOPRECIPITATE' => false,
    ],
    'default_require_crossmatch' => true,

    /*
    |--------------------------------------------------------------------------
    | Emergency blood release
    |--------------------------------------------------------------------------
    */
    'emergency_release_types' => [
        'UNCROSSMATCHED_O_NEGATIVE',
        'UNCROSSMATCHED_GROUP_SPECIFIC',
        'EMERGENCY_INCOMPATIBLE_OVERRIDE',
    ],

    // Clinical indication catalogue (suggestions only).
    'clinical_indications' => [
        'Severe anemia', 'Acute bleeding', 'Surgery preparation',
        'Postpartum hemorrhage', 'Trauma', 'Exchange transfusion',
        'Thrombocytopenia', 'Coagulopathy', 'Burns', 'Massive transfusion protocol',
    ],
];
