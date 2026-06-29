<?php

use App\Enums\VisitStatus;

return [

    /*
    |--------------------------------------------------------------------------
    | Stage delay thresholds (minutes)
    |--------------------------------------------------------------------------
    | How long a patient may spend in a journey stage before being flagged.
    | Fully configurable — override per deployment. Keyed by PatientJourneyStage
    | value. `delayed` raises a warning, `critical` raises an alert.
    */
    'thresholds' => [
        'registered'    => ['delayed' => 20,  'critical' => 40],
        'checked_in'    => ['delayed' => 30,  'critical' => 60],
        'consultation'  => ['delayed' => 60,  'critical' => 120],
        'investigation' => ['delayed' => 120, 'critical' => 240],
        'procedure'     => ['delayed' => 90,  'critical' => 180],
        'treatment'     => ['delayed' => 90,  'critical' => 180],
        'pharmacy'      => ['delayed' => 45,  'critical' => 90],
        'admission'     => ['delayed' => 240, 'critical' => 480],
        'discharge'     => ['delayed' => 120, 'critical' => 240],
    ],

    /* Fallback when a stage has no explicit threshold. */
    'default_threshold' => ['delayed' => 90, 'critical' => 180],

    /*
    |--------------------------------------------------------------------------
    | Active statuses
    |--------------------------------------------------------------------------
    | Visit statuses that mean the patient is still moving through the hospital
    | (used by the bottleneck aggregation). Excludes intake-only and terminal
    | states.
    */
    'active_statuses' => [
        VisitStatus::QUEUED->value,
        VisitStatus::TRIAGE->value,
        VisitStatus::WAITING->value,
        VisitStatus::CONSULTING->value,
        VisitStatus::ACTIVE->value,
        VisitStatus::REFERRED_CONSULTATION->value,
        VisitStatus::WAITING_INVESTIGATION->value,
        VisitStatus::LAB->value,
        VisitStatus::PHARMACY->value,
        VisitStatus::BILLING->value,
        VisitStatus::ADMITTING->value,
        VisitStatus::ADMITTED->value,
        VisitStatus::INPATIENT->value,
        VisitStatus::DISCHARGING->value,
        VisitStatus::EMERGENCY->value,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-department handoff SLAs (Phase 9.4)
    |--------------------------------------------------------------------------
    | Target minutes within which the OWNING department should resolve each
    | delay cause once a patient is waiting on it. Keyed by JourneyDelayCause
    | value. Fully configurable per deployment.
    */
    'cause_sla' => [
        'awaiting_consultation'      => 60,
        'awaiting_payment'           => 30,
        'awaiting_lab_request'       => 30,
        'awaiting_lab_result'        => 120,
        'awaiting_radiology_request' => 30,
        'awaiting_radiology_result'  => 120,
        'awaiting_procedure'         => 120,
        'awaiting_prescription'      => 30,
        'awaiting_dispensing'        => 45,
        'awaiting_admission'         => 120,
        'awaiting_bed'               => 90,
        'awaiting_discharge'         => 120,
        'awaiting_clinical_review'   => 60,
    ],

    /* Fallback SLA (minutes) when a cause has no explicit entry. */
    'default_cause_sla' => 90,

    /*
    | SLA evaluation tuning:
    |  - near_breach_ratio: fraction of the SLA elapsed before warning (0.8 = 80%).
    |  - critical_breach_multiplier: multiple of the SLA that escalates to critical.
    */
    'sla' => [
        'near_breach_ratio' => 0.8,
        'critical_breach_multiplier' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Worklist live refresh (Phase 9.5)
    |--------------------------------------------------------------------------
    | Lightweight polling for the journey worklist. No websockets. Disable by
    | setting `enabled` to false — the page falls back to the manual button.
    */
    'worklist_refresh' => [
        'enabled' => true,
        'interval_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Handoff notifications (Phase 9.6)
    |--------------------------------------------------------------------------
    | Channel toggles for journey coordination notifications. In-app uses the
    | existing NotificationService (which itself respects per-user channel
    | preferences). Email/SMS are OFF by default and only ride existing,
    | configured providers — no new external infrastructure.
    */
    'notifications' => [
        'in_app' => true,
        'email' => false,
        'sms' => false,
        'dedupe_minutes' => 60,
    ],

    /*
    | Scheduled escalation sweep. Idempotent + safe to run repeatedly. Disable to
    | fall back to on-view escalation (Phase 9.5).
    */
    'handoff_escalation' => [
        'enabled' => env('JOURNEY_HANDOFF_ESCALATION_ENABLED', true),
        'frequency' => 'everyFiveMinutes',
        'batch_limit' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalation routing policy (Phase 9.7)
    |--------------------------------------------------------------------------
    | Drives the supervisor resolver + the unassigned sweep. Defaults prefer the
    | department supervisor, fall back to eligible staff, and cap recipients.
    */
    'escalation_policy' => [
        'notify_near_breach_unassigned' => true,
        'notify_breached_unassigned' => true,
        'notify_critical_unassigned' => true,
        'prefer_supervisor' => true,
        'fallback_to_eligible_staff' => true,
        'route_critical_to_oversight' => true,
        'max_recipients' => 8,
    ],

    /*
    | Notification digest foundation (Phase 9.7). Deferred — no scheduler built yet.
    | See docs/journey/notification-preferences.md.
    */
    'notification_digest' => [
        'enabled' => false,
        'frequency' => 'hourly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics snapshots (Phase 9.8)
    |--------------------------------------------------------------------------
    | Daily aggregate snapshot build. Idempotent. `cache_ttl`/`report_cache_ttl`
    | (seconds) bound how long analytics query results are cached per scope.
    */
    'analytics_snapshot' => [
        'enabled' => env('JOURNEY_ANALYTICS_SNAPSHOT_ENABLED', true),
        'daily_time' => '00:30',
        'hourly_enabled' => false,
    ],

    'analytics' => [
        'cache_ttl' => 300,          // dashboard analytics (5 min)
        'report_cache_ttl' => 1800,  // historical reports (30 min)
        'default_days' => 7,
        'max_days' => 92,
    ],

    /*
    |--------------------------------------------------------------------------
    | Predictive breach risk (Phase 9.9)
    |--------------------------------------------------------------------------
    | Explainable, weighted heuristics (no ML). Baselines come from Phase 9.8
    | aggregate snapshots; live state from Phase 9.4–9.7. Safe with no history.
    */
    'prediction' => [
        'enabled' => true,
        'history_days' => 30,
        'minimum_snapshot_count' => 5,
        'near_breach_buffer_minutes' => 15,
        'critical_breach_ratio' => 2.0,
        'risk_weights' => [
            'elapsed_ratio' => 40,
            'historical_breach_rate' => 25,
            'current_assignment_state' => 15,
            'department_pressure' => 10,
            'escalation_state' => 10,
        ],
        'confidence' => [
            'low_sample_threshold' => 5,
            'medium_sample_threshold' => 20,
        ],
        'cache' => [
            'baseline_ttl' => 1800,   // 30 min
            'active_ttl' => 60,       // 60 s
            'summary_ttl' => 60,      // 60 s
            'forecast_ttl' => 300,    // 5 min
        ],
    ],

    /* Prediction alerts — OFF by default; reuse Phase 9.7 routing/preferences. */
    'prediction_alerts' => [
        'enabled' => false,
        'notify_high_risk' => false,
        'notify_critical_risk' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Prediction accuracy (Phase 9.10)
    |--------------------------------------------------------------------------
    | Capture predictions now, evaluate them against actual outcomes later. The
    | outcome table stores a HASHED identity — no patient names/visit numbers.
    */
    'prediction_accuracy' => [
        'enabled' => env('JOURNEY_PREDICTION_ACCURACY_ENABLED', true),
        'capture_frequency' => 'hourly',
        'evaluation_frequency' => 'daily',
        'evaluation_delay_hours' => 24,
    ],

    /* Predictive escalation advisor (Phase 9.10) — ADVISORY only; never auto-acts. */
    'predictive_escalation' => [
        'enabled' => false,
    ],
];
