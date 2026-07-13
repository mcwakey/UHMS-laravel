<?php

return [
    'title' => 'Departmental Payment Enforcement',
    'subtitle' => 'Review how workflow operations connect to payment timing. Runtime payment gates now use the typed visit payment policies when they are enabled.',

    // Column / field labels
    'operation' => 'Operation',
    'workflow_stage' => 'Workflow Stage',
    'department' => 'Department',
    'current_status' => 'Current Production Status',
    'current_behaviour' => 'Current Enforcement Behaviour',
    'configured_mode' => 'Configured Mode',
    'missing_billing_context' => 'Missing Billing Context',
    'visit_context_rule' => 'Visit Context Rule',
    'override_scope_label' => 'Override Scope',
    'emergency_exempt' => 'Emergency Exempt',
    'inpatient_exempt' => 'Inpatient Exempt',
    'typed_enforcement_eligibility' => 'Typed Enforcement Eligibility',
    'legacy_behaviour' => 'Legacy Behaviour',
    'eligibility_status' => 'Eligibility',
    'compatibility_status' => 'Compatibility',

    // Safety indicators
    'currently_wired' => 'Currently Wired',
    'currently_unwired' => 'No Active Gate Call',
    'existing_hard_gate' => 'Payment Gate Call',
    'display_readiness_only' => 'Display / Readiness Only',
    'compatibility_specific_rule' => 'Compatibility-Specific Rule',

    // Notices / actions
    'read_only_notice' => 'Rows marked as active gate calls are production workflow checkpoints. Their runtime payment behaviour is unified through Payment Timing Policies.',
    'not_operational_notice' => 'Payment Timing Policies now control every active payment gate call. Rows without an active gate call are workflow coverage notes only and are not blocking patients.',
    'configuration_not_operational' => 'Coverage note only',
    'save' => 'Save',
    'updated_successfully' => 'Departmental payment enforcement settings updated.',
    'no_operations' => 'No operations are registered.',
    'yes' => 'Yes',
    'no' => 'No',
    'not_applicable' => 'Not Applicable',
    'eligible' => 'Eligible',
    'not_eligible' => 'Not Eligible',

    'modes' => [
        'disabled' => 'Disabled',
        'observe' => 'Observe Only',
        'legacy' => 'Compatibility Metadata',
        'typed' => 'Typed Payment Policy',
    ],

    'missing_context' => [
        'preserve_legacy' => 'Preserve Existing Behaviour',
        'allow' => 'Allow',
        'block' => 'Block',
        'not_applicable' => 'Not Applicable',
    ],

    'visit_context' => [
        'use_visit_policy' => 'Use Visit Policy',
        'always_running_bill' => 'Always Running Bill',
        'preserve_legacy' => 'Preserve Existing Behaviour',
        'not_applicable' => 'Not Applicable',
    ],

    'override_scope' => [
        'none' => 'None',
        'invoice_item_only' => 'Invoice Item Only',
        'service_or_item' => 'Service or Item',
        'department_service_or_item' => 'Department, Service or Item',
        'visit_wide' => 'Visit-Wide',
        'preserve_legacy' => 'Preserve Existing Behaviour',
    ],

    'eligibility' => [
        'eligible' => 'Eligible',
        'ineligible_unwired' => 'No Active Gate Call',
        'ineligible_missing_stage' => 'Not Eligible — Missing Stage',
        'ineligible_missing_invoice_resolution' => 'Not Eligible — Invoice Resolution Missing',
        'ineligible_emergency_boundary' => 'Not Eligible — Emergency Boundary Missing',
        'ineligible_compatibility_rule' => 'Not Eligible — Compatibility Rule',
        'ineligible_unapproved_policy' => 'Not Eligible — Policy Not Approved',
    ],

    'compatibility' => [
        'compatible' => 'Compatible',
        'configuration_non_operational' => 'Coverage Note',
        'legacy_hard_gate_protected' => 'Unified by Payment Timing Policy',
        'typed_cutover_not_ready' => 'Typed Policy Metadata',
        'unwired_operation' => 'No Active Gate Call',
        'emergency_boundary_missing' => 'Emergency Boundary Missing',
        'invoice_resolution_missing' => 'Invoice Resolution Missing',
        'compatibility_rule_conflict' => 'Compatibility Rule Conflict',
    ],

    'family' => [
        'consultation' => 'Consultation',
        'laboratory' => 'Laboratory',
        'pharmacy' => 'Pharmacy',
        'investigation' => 'Investigations & Radiology',
        'procedure' => 'Procedures',
        'service' => 'Service Rendering',
        'theatre' => 'Theatre',
        'treatment' => 'Treatment',
        'nursing' => 'Nursing',
        'blood_bank' => 'Blood Bank',
        'ambulance' => 'Ambulance',
        'other' => 'Other',
    ],

    'errors' => [
        'duplicate_operation' => 'This operation was submitted more than once.',
        'unknown_operation' => 'Unknown operation code.',
        'hard_gate_read_only' => 'This operation is an active payment-gate call and is controlled centrally by Payment Timing Policies.',
    ],
];
