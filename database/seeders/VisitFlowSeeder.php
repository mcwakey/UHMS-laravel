<?php

namespace Database\Seeders;

use App\Models\AttendanceClass;
use App\Models\VisitSource;
use Illuminate\Database\Seeder;

/**
 * Seeds the dynamic visit-flow lookup vocabulary. Idempotent (updateOrCreate on
 * code) so it can be re-run safely. Extend the flow later by adding rows here.
 */
class VisitFlowSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['code' => 'direct',         'name' => 'Direct',          'color' => 'secondary', 'description' => 'Patient came directly to the facility.'],
            ['code' => 'appointment',    'name' => 'Appointment',     'color' => 'info',      'description' => 'Visit created from a scheduled appointment.'],
            ['code' => 'emergency',      'name' => 'Emergency',       'color' => 'danger',    'description' => 'Visit started through the emergency department.'],
            ['code' => 'referral',       'name' => 'Referral',        'color' => 'warning',   'description' => 'Referred from another provider or department.'],
            ['code' => 'follow_up',      'name' => 'Follow Up',       'color' => 'primary',   'description' => 'Follow-up of a previous visit.'],
            ['code' => 'review',         'name' => 'Review',          'color' => 'primary',   'description' => 'Review visit.'],
            ['code' => 'online_booking', 'name' => 'Online Booking',  'color' => 'info',      'description' => 'Self-booked online.'],
            ['code' => 'walk_in',        'name' => 'Walk In',         'color' => 'secondary', 'description' => 'Unscheduled walk-in patient.'],
        ];

        foreach ($sources as $i => $row) {
            VisitSource::updateOrCreate(
                ['code' => $row['code']],
                array_merge($row, ['is_active' => true, 'sort_order' => $i + 1]),
            );
        }

        $classes = [
            ['code' => 'first_ever',               'name' => 'First Ever',                'color' => 'success', 'description' => 'Patient has no previous attendance in the system.'],
            ['code' => 'first_attendance_of_year', 'name' => 'First Attendance of Year',  'color' => 'info',    'description' => 'First attendance in the current calendar year.'],
            ['code' => 'subsequent_attendance',    'name' => 'Subsequent Attendance',     'color' => 'secondary', 'description' => 'Patient already attended at least once this year.'],
            ['code' => 'emergency_attendance',     'name' => 'Emergency Attendance',      'color' => 'danger',  'description' => 'Attendance through the emergency department.'],
            ['code' => 'referral_attendance',      'name' => 'Referral Attendance',       'color' => 'warning', 'description' => 'Attendance arising from a referral.'],
        ];

        foreach ($classes as $i => $row) {
            AttendanceClass::updateOrCreate(
                ['code' => $row['code']],
                array_merge($row, ['is_active' => true, 'sort_order' => $i + 1]),
            );
        }
    }
}
