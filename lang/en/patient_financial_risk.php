<?php

return [
    'title' => 'Financial Risk',
    'section_title' => 'Patient Financial Risk',
    'worklist_title' => 'Financial-Risk Worklist',
    'report_title' => 'Financial-Risk Report',
    'subtitle' => 'Controlled administrative classification of payment flexibility. This is not a payment decision.',
    'empty_state' => 'No active financial-risk classification for this patient.',
    'no_results' => 'No financial-risk profiles match the selected filters.',
    'not_authorised' => 'You are not authorised to view financial-risk information.',

    'fields' => [
        'risk_level' => 'Risk Level',
        'primary_reason' => 'Primary Reason',
        'reason_details' => 'Reason Details',
        'credit_limit' => 'Credit Limit',
        'status' => 'Status',
        'effective_from' => 'Effective Date',
        'review_due_at' => 'Review Due',
        'expires_at' => 'Expiry Date',
        'reference' => 'Reference',
        'set_by' => 'Set By',
        'reviewed_by' => 'Last Reviewed By',
        'reviewed_at' => 'Reviewed At',
        'cleared_by' => 'Cleared By',
        'last_updated' => 'Last Updated',
        'patient' => 'Patient',
    ],

    'levels' => [
        'normal' => [
            'label' => 'Normal',
            'description' => 'No active financial restriction is recorded.',
        ],
        'watchlist' => [
            'label' => 'Watchlist',
            'description' => 'Requires additional financial review but is not automatically denied payment flexibility.',
        ],
        'high_risk' => [
            'label' => 'High Risk',
            'description' => 'Material financial exposure requiring controlled approval before extending credit in a future phase.',
        ],
        'blocked_credit' => [
            'label' => 'Blocked Credit',
            'description' => 'New credit or deferred-settlement arrangements are formally restricted, subject to authorised override in a future phase.',
        ],
    ],

    'reasons' => [
        'previous_unpaid_visits' => 'Previous unpaid visits',
        'repeated_abandoned_invoices' => 'Repeated abandoned invoices',
        'credit_limit_exceeded' => 'Credit limit exceeded',
        'invalid_corporate_guarantee' => 'Invalid corporate guarantee',
        'insurance_eligibility_unresolved' => 'Insurance eligibility unresolved',
        'payment_commitment_breached' => 'Payment commitment breached',
        'management_decision' => 'Management decision',
        'other' => 'Other',
    ],

    'statuses' => [
        'active' => 'Active',
        'under_review' => 'Under Review',
        'suspended' => 'Suspended',
        'cleared' => 'Cleared',
        'expired' => 'Expired',
    ],

    'events' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'submitted_for_review' => 'Submitted for review',
        'review_completed' => 'Review completed',
        'suspended' => 'Suspended',
        'reactivated' => 'Reactivated',
        'cleared' => 'Cleared',
        'expired' => 'Expired',
    ],

    'actions' => [
        'classify' => 'Classify Patient',
        'edit' => 'Edit Classification',
        'submit_review' => 'Submit for Review',
        'complete_review' => 'Complete Review',
        'suspend' => 'Suspend',
        'reactivate' => 'Reactivate',
        'clear' => 'Clear Restriction',
        'view_history' => 'View History',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'export' => 'Export CSV',
        'open_worklist' => 'Open Worklist',
    ],

    'badges' => [
        'review_overdue' => 'Review Overdue',
        'expires_soon' => 'Expires Soon',
        'finance_review_required' => 'Financial review required',
    ],

    'history' => [
        'title' => 'Financial-Risk History',
        'datetime' => 'Date / Time',
        'event' => 'Event',
        'change' => 'Change',
        'performed_by' => 'Performed By',
        'reason' => 'Reason',
        'empty' => 'No history recorded.',
        'from_to' => ':from → :to',
        'system' => 'System',
    ],

    'filters' => [
        'search' => 'Search patient',
        'level' => 'Risk level',
        'status' => 'Status',
        'review_due' => 'Due for review',
        'expired' => 'Expired',
        'restriction' => 'Active restriction',
        'all' => 'All',
        'apply' => 'Apply',
        'reset' => 'Reset',
    ],

    'report' => [
        'active_watchlist' => 'Active watchlist patients',
        'active_high_risk' => 'Active high-risk patients',
        'active_blocked_credit' => 'Active blocked-credit patients',
        'due_for_review' => 'Profiles due for review',
        'expiring_soon' => 'Profiles expiring soon',
        'cleared_this_month' => 'Cleared this month',
        'created_this_month' => 'Created this month',
    ],

    'export' => [
        'patient_number' => 'Patient Number',
        'patient_name' => 'Patient Name',
    ],

    'form' => [
        'classify_heading' => 'Classify Patient Financial Risk',
        'edit_heading' => 'Edit Financial-Risk Classification',
        'reason_placeholder' => 'Provide supporting context',
        'clearance_reason' => 'Clearance reason',
        'suspend_reason' => 'Suspension reason',
        'reactivate_reason' => 'Reactivation reason',
        'review_note' => 'Review note (optional)',
        'credit_limit_help' => 'Informational only in this phase — not enforced against invoices.',
    ],

    'validation' => [
        'details_required_for_other' => 'Reason details are required when the reason is "Other".',
        'details_or_reference_required_for_management' => 'A management decision requires reason details or a reference.',
    ],

    'flash' => [
        'classified' => 'Financial-risk classification saved.',
        'updated' => 'Financial-risk classification updated.',
        'submitted_for_review' => 'Profile submitted for review.',
        'review_completed' => 'Review completed.',
        'suspended' => 'Profile suspended.',
        'reactivated' => 'Profile reactivated.',
        'cleared' => 'Financial-risk restriction cleared.',
        'invalid_transition' => 'That status change is not allowed.',
    ],
];
