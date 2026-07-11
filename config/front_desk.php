<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Front Desk Operations (Phase 18A)
    |--------------------------------------------------------------------------
    |
    | Non-clinical reception / facility desk activities: visitor logs, call logs
    | and courier logs. This module is deliberately separate from clinical
    | patient "visits" — see App\Models\FrontDeskVisitorLog. Front desk screens
    | never surface diagnosis, clinical notes, medications or lab results.
    |
    */

    // A checked-in visitor with no checkout whose time_in is older than this
    // many hours is flagged as "overdue" on the front desk dashboard.
    'visitor_overdue_hours' => (int) env('FRONT_DESK_VISITOR_OVERDUE_HOURS', 4),

    // How many recent records each dashboard "recent activity" panel shows.
    'dashboard_recent_limit' => (int) env('FRONT_DESK_DASHBOARD_RECENT_LIMIT', 8),

    // Default pagination size for front desk list screens.
    'per_page' => (int) env('FRONT_DESK_PER_PAGE', 20),

    /*
    |--------------------------------------------------------------------------
    | Patient Visitor Management (Phase 18B)
    |--------------------------------------------------------------------------
    |
    | Settings for managing people visiting admitted patients: badge/pass number
    | generation, advisory visitor limits and discharge warnings. Every limit
    | here is an ADVISORY warning (never a hard block) so authorised staff can
    | always proceed. `visitors.overdue_hours` mirrors the top-level
    | `visitor_overdue_hours` above so existing Phase 18A overdue logic is
    | preserved; new code prefers this key and falls back to the old one.
    |
    */
    'visitors' => [
        'auto_generate_badge_number' => (bool) env('FRONT_DESK_AUTO_BADGE', true),
        'badge_prefix' => env('FRONT_DESK_BADGE_PREFIX', 'VIS'),
        'max_active_visitors_per_patient' => 2,
        'max_active_visitors_per_admission' => 2,
        'max_active_visitors_per_ward' => null,
        'default_visit_duration_minutes' => 120,
        'overdue_hours' => (int) env('FRONT_DESK_VISITOR_OVERDUE_HOURS', 4),
        'allow_visitors_for_discharged_same_day' => true,
        'show_discharge_warning' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Call follow-up queue & courier workflow (Phase 18C)
    |--------------------------------------------------------------------------
    |
    | A callback with a follow_up_due_at older than now (plus the grace minutes)
    | is "overdue". A dispatched/in-transit courier older than couriers.overdue_hours
    | is "overdue" on the workflow dashboard.
    |
    */
    'calls' => [
        'callback_overdue_minutes' => (int) env('FRONT_DESK_CALLBACK_OVERDUE_MINUTES', 0),
    ],
    'couriers' => [
        'overdue_hours' => (int) env('FRONT_DESK_COURIER_OVERDUE_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Handover, Lost & Found, Incident Desk (Phase 18E)
    |--------------------------------------------------------------------------
    |
    | Operational, non-clinical reception/security workflows.
    |
    */
    'handovers' => [
        'recent_limit' => 10,
    ],
    'lost_found' => [
        'reference_prefix' => env('FRONT_DESK_LOST_FOUND_PREFIX', 'LF'),
    ],
    'incidents' => [
        'reference_prefix' => env('FRONT_DESK_INCIDENT_PREFIX', 'INC'),
        'dashboard_recent_limit' => 10,
    ],
];
