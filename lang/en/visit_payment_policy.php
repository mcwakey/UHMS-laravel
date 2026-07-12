<?php

return [
    'title' => 'Visit Payment Policy',
    'worklist_title' => 'Visit Payment-Policy Worklist',
    'report_title' => 'Visit Payment-Policy Report',
    'section_title' => 'Payment Policy',
    'observational_notice' => 'This record is observational and does not currently control service access or payment enforcement.',
    'empty_state' => 'No materialised payment policy for this visit.',
    'no_results' => 'No visit payment policies match the selected filters.',

    'labels' => [
        'observed_policy' => 'Observed Policy',
        'recommended_policy' => 'Recommended Policy',
        'operational_legacy_gate' => 'Operational Legacy Gate',
        'policy_source' => 'Policy Source',
        'resolution_reason' => 'Resolution Reason',
        'finance_review_required' => 'Finance Review Required',
        'risk_snapshot' => 'Risk Snapshot',
        'risk_level_snapshot' => 'Risk Level Snapshot',
        'risk_status_snapshot' => 'Risk Status Snapshot',
        'risk_recommendation' => 'Risk Recommendation',
        'global_default_snapshot' => 'Global Default Snapshot',
        'visit_type_policy_snapshot' => 'Visit-Type Policy Snapshot',
        'visit_type_snapshot' => 'Visit Type',
        'emergency_protection' => 'Emergency Protection Considered',
        'compatible_override' => 'Compatible Visit Override',
        'materialized_at' => 'Materialised At',
        'last_refreshed' => 'Last Refreshed',
        'snapshot_freshness' => 'Snapshot Freshness',
        'resolution_version' => 'Resolution Version',
        'visit' => 'Visit',
        'patient' => 'Patient',
        'no_recommendation' => 'No recommendation',
        'prepayment_recommended' => 'Prepayment recommended',
        'none' => 'None',
        'considered' => 'Considered',
        'not_considered' => 'Not considered',
    ],

    'freshness' => [
        'current' => 'Snapshot current',
        'stale' => 'Snapshot stale',
    ],

    'events' => [
        'materialized' => 'Materialised',
        'refreshed' => 'Refreshed',
        'risk_snapshot_changed' => 'Risk snapshot changed',
        'baseline_policy_changed' => 'Baseline policy refreshed',
        'recommendation_changed' => 'Recommendation changed',
        'marked_stale' => 'Marked stale',
    ],

    'history' => [
        'title' => 'Visit policy history',
        'datetime' => 'Date / Time',
        'event' => 'Event',
        'previous_policy' => 'Previous Policy',
        'new_policy' => 'New Policy',
        'previous_recommendation' => 'Previous Recommendation',
        'new_recommendation' => 'New Recommendation',
        'risk_change' => 'Risk Snapshot Change',
        'performed_by' => 'Performed By',
        'reason_code' => 'Reason Code',
        'empty' => 'No history recorded.',
        'system' => 'System',
    ],

    'filters' => [
        'resolved_policy' => 'Observed policy',
        'recommended_policy' => 'Recommended policy',
        'resolution_source' => 'Source',
        'risk_level' => 'Risk level snapshot',
        'visit_type' => 'Visit type',
        'finance_review' => 'Finance review required',
        'active_only' => 'Active visits only',
        'from' => 'Materialised from',
        'to' => 'Materialised to',
        'all' => 'All',
        'apply' => 'Apply',
        'reset' => 'Reset',
    ],

    'report' => [
        'total' => 'Materialised policies',
        'finance_review' => 'Requiring finance review',
        'with_recommendation' => 'With a recommendation',
        'high_risk_snapshot' => 'High-risk snapshots',
        'blocked_credit_snapshot' => 'Blocked-credit snapshots',
        'materialized_this_month' => 'Materialised this month',
    ],

    'actions' => [
        'refresh' => 'Refresh Snapshot',
        'view' => 'View',
        'open_worklist' => 'Open Worklist',
        'report' => 'Report',
    ],

    'flash' => [
        'refreshed' => 'Visit payment policy refreshed.',
    ],
];
