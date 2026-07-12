<?php

return [
    'title' => 'Operational Payment Cutover',
    'master_controls' => 'Master Controls',
    'rollback_title' => 'Immediate Rollback',
    'eligible_operations' => 'Eligible Operations',
    'no_operations' => 'No operations are eligible for typed cutover.',
    'view_only' => 'You have view-only access.',
    'on' => 'On',
    'off' => 'Off',
    'emergency_not_supported' => 'Emergency Not Supported',
    'force_legacy_on' => 'Environment force-legacy is ON',
    'force_legacy_help' => 'All payment operations run on legacy authority regardless of these settings until the environment kill switch is cleared.',
    'master_help' => 'Changing to Active lets operations configured as Typed return typed decisions. Everything else stays on legacy authority.',
    'rollback_help' => 'Return all payment operations to legacy authority immediately. Approved arrangements are preserved.',

    'modes' => [
        'disabled' => 'Disabled',
        'observe' => 'Observe Only',
        'active' => 'Active',
    ],

    'labels' => [
        'configured_mode' => 'Configured Mode',
        'effective_mode' => 'Effective Mode',
        'force_legacy' => 'Environment Force Legacy',
        'failure_fallback' => 'Legacy Fallback on Failure',
        'supported_visit_types' => 'Supported Visit Types',
        'reason' => 'Cutover Reason',
        'confirm_active' => 'I confirm activating operational typed enforcement.',
        'acknowledge_compatibility' => 'I acknowledge the compatibility change.',
    ],

    'actions' => [
        'save_master' => 'Save Master Mode',
        'save_operation' => 'Save',
        'rollback' => 'Return All Operations to Legacy',
    ],

    'blockers' => [
        'force_legacy' => 'Force-legacy active',
        'master_not_active' => 'Master not active',
        'acknowledgement_missing' => 'Acknowledgement missing',
    ],

    'reasons' => [
        'TYPED_PREPAYMENT_REQUIRED' => 'Payment is required before this service can proceed.',
        'TYPED_PREPAYMENT_SETTLED' => 'Pre-service settlement requirements are met.',
        'TYPED_PAY_AFTER_SERVICES_ALLOWED' => 'Payment after services is allowed for this visit.',
        'TYPED_RUNNING_BILL_ALLOWED' => 'This visit is on a running bill; the service may proceed.',
        'APPROVED_ARRANGEMENT_PREPAYMENT_REQUIRED' => 'An approved arrangement requires prepayment before this service.',
        'APPROVED_ARRANGEMENT_DEFERRED_SETTLEMENT' => 'An approved arrangement allows deferred settlement.',
        'APPROVED_ARRANGEMENT_RUNNING_BILL' => 'An approved arrangement allows a running bill.',
        'APPROVED_ARRANGEMENT_INELIGIBLE' => 'The approved arrangement is not operationally eligible.',
        'TYPED_OPERATION_UNSUPPORTED' => 'Typed enforcement is not supported for this operation.',
        'TYPED_EMERGENCY_FALLBACK' => 'Emergency care follows legacy authority.',
        'TYPED_FAILURE_LEGACY_FALLBACK' => 'A technical issue occurred; legacy authority applies.',
        'TYPED_LEGACY_OVERRIDE_CONFLICT' => 'A conflicting legacy override applies; the arrangement is ignored.',
    ],

    'errors' => [
        'activate_not_permitted' => 'You are not permitted to activate operational cutover.',
        'confirmation_required' => 'Please confirm activation.',
        'unknown_operation' => 'Unknown operation.',
        'operation_not_typed_eligible' => 'This operation is not eligible for typed enforcement.',
        'acknowledgement_required' => 'A compatibility acknowledgement is required for typed mode.',
    ],

    'flash' => [
        'master_updated' => 'Master cutover mode updated.',
        'operation_updated' => 'Operation cutover mode updated.',
        'rolled_back' => 'All payment operations returned to legacy authority.',
    ],
];
