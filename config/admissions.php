<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bed release behavior
    |--------------------------------------------------------------------------
    |
    | Keep the default compatible with the current workflow. Set to "cleaning"
    | when the site wants discharged beds to require a housekeeping release.
    |
    */
    'bed_release_after_discharge' => env('ADMISSION_BED_RELEASE_AFTER_DISCHARGE', 'available'),

    'reservations' => [
        'default_expiry_hours' => (int) env('ADMISSION_BED_RESERVATION_EXPIRY_HOURS', 6),
        'auto_expiry_schedule_enabled' => (bool) env('ADMISSION_BED_RESERVATION_AUTO_EXPIRE', false),
    ],

    'vitals_overdue_hours' => (int) env('ADMISSION_VITALS_OVERDUE_HOURS', 8),
    'ward_round_overdue_hours' => (int) env('ADMISSION_WARD_ROUND_OVERDUE_HOURS', 24),

    'discharge' => [
        'require_clearance_before_discharge' => (bool) env('ADMISSION_REQUIRE_DISCHARGE_CLEARANCE', false),
        'require_summary_before_discharge' => (bool) env('ADMISSION_REQUIRE_DISCHARGE_SUMMARY', false),
        'require_billing_clearance_before_discharge' => (bool) env('ADMISSION_REQUIRE_BILLING_CLEARANCE', false),
    ],
];
