<?php

/*
|--------------------------------------------------------------------------
| Visit Payment Arrangement (Payment Timing Policy Phase 7)
|--------------------------------------------------------------------------
|
| Administrative request/approval workflow settings. Nothing here activates
| enforcement — an approved arrangement is administrative only in Phase 7.
|
*/

return [
    // A previous patient-responsibility balance at or above this amount makes a
    // deferred-payment request require separate finance-manager approval.
    'previous_balance_threshold' => 0.0,

    // Requests awaiting review longer than this many hours are surfaced in the
    // worklist/report as overdue.
    'review_overdue_hours' => 24,

    // Exceptional self-approval is OFF by default; only the dedicated permission
    // 'visits.payment_arrangement.self_approve' (granted to no role by default)
    // can bypass requester≠approver.
    'allow_self_approval_permission' => 'visits.payment_arrangement.self_approve',
];
