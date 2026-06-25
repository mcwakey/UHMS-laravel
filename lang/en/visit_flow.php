<?php

/*
| Dynamic visit-flow vocabulary: visit sources, attendance classes, UI labels and
| flash messages. Status labels themselves live in statuses.php (visit domain).
*/

return [

    'source' => [
        'direct' => 'Direct',
        'appointment' => 'Appointment',
        'emergency' => 'Emergency',
        'referral' => 'Referral',
        'follow_up' => 'Follow Up',
        'review' => 'Review',
        'online_booking' => 'Online Booking',
        'walk_in' => 'Walk In',
    ],

    'attendance_class' => [
        'first_ever' => 'First Ever',
        'first_attendance_of_year' => 'First Attendance of Year',
        'subsequent_attendance' => 'Subsequent Attendance',
        'emergency_attendance' => 'Emergency Attendance',
        'referral_attendance' => 'Referral Attendance',
    ],

    'ui' => [
        'badge_first_ever' => 'First ever',
        'badge_first_attendance_of_year' => 'First attendance this year',
        'badge_subsequent_attendance' => 'Subsequent attendance',
        'badge_emergency_attendance' => 'Emergency attendance',
        'badge_referral_attendance' => 'Referral attendance',
        'attendance_label' => 'Attendance',
        'source_label' => 'Source',
        'status_label' => 'Status',
        'status_flow' => 'Visit Status Flow',
        'status_history' => 'Status History',
        'computing' => 'Checking attendance…',
    ],

    'messages' => [
        'transition_blocked' => 'Cannot move the visit from :from to :to.',
        'override_required' => 'You do not have permission to override this transition.',
        'transitioned' => 'Visit status updated to :status.',
    ],
];
