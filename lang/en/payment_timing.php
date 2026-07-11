<?php

return [
    'title' => 'Payment Timing Policies',
    'enable' => 'Enable configurable payment timing',
    'enable_help' => 'Stores policy preferences for later phases. Enabling this setting does not yet change or block service delivery.',
    'global_default' => 'Global default',
    'visit_type_defaults' => 'Visit-type defaults',
    'emergency_protection' => 'Emergency protection',
    'never_block_stabilisation' => 'Never block emergency stabilisation because payment is pending',
    'emergency_help' => 'Emergency stabilisation remains protected; final billing or financial clearance can occur later.',
    'financial_closure' => 'Financial closure',
    'require_pay_after_settlement' => 'Require settlement before financial closure for Pay After All Services',
    'require_running_bill_settlement' => 'Require settlement before financial closure for Running Bill',
    'allow_outstanding_override' => 'Allow authorised outstanding-balance closure overrides',
    'financial_closure_help' => 'These preferences are preparatory and do not yet alter visit completion or discharge.',
    'updated_successfully' => 'Payment timing settings updated successfully.',
    'invalid_policy' => 'Invalid payment timing policy.',
    'global_cannot_inherit' => 'The global payment timing policy cannot use the system default.',
    'unknown_visit_type' => 'Unknown visit type policy.',
    'integration' => [
        'legacy' => 'Legacy mode',
        'observe' => 'Observation mode',
        'source' => 'Payment policy source',
        'match' => 'Legacy policy matches',
        'legacy_more_restrictive' => 'Legacy policy is more restrictive',
        'typed_more_restrictive' => 'Typed policy is more restrictive',
        'not_comparable' => 'Comparison unavailable',
        'missing_context' => 'Required comparison context is missing',
        'invalid_configuration_fallback' => 'Invalid configuration fallback',
    ],
    'policies' => [
        'inherit' => [
            'label' => 'Use System Default',
            'description' => 'Use the hospital-wide payment timing policy.',
        ],
        'pay_before_service' => [
            'label' => 'Pay Before Service',
            'description' => 'Payment is required before applicable billable services proceed.',
        ],
        'pay_after_all_services' => [
            'label' => 'Pay After All Services',
            'description' => 'The patient may complete services before making the final payment.',
        ],
        'running_bill' => [
            'label' => 'Running Bill',
            'description' => 'Charges accumulate while services continue, and partial payments may be recorded.',
        ],
    ],
    'sources' => [
        'global_default' => 'Global default',
        'visit_type' => 'Visit type',
        'patient_risk' => 'Patient financial risk',
        'insurance' => 'Insurance',
        'corporate_account' => 'Corporate account',
        'manual_override' => 'Manual override',
        'emergency_policy' => 'Emergency policy',
    ],
];
