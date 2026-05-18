<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

/**
 * Seeds a small spread of appointments around "today" so the Reception /
 * Doctor schedule view has data. Mix of statuses: completed (past),
 * confirmed & scheduled (today & near future), one cancelled.
 */
class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $doctorRole = Role::where('name', 'Doctor')->first();
        $doctors = $doctorRole
            ? User::role('Doctor')->get()
            : User::whereIn('email', [
                'doctor@uhms.local',
                'pediatrician@uhms.local',
                'surgeon@uhms.local',
                'obgyn@uhms.local',
            ])->get();

        $patients = Patient::inRandomOrder()->limit(30)->get();
        $admin = User::where('email', 'admin@uhms.local')->first() ?? User::first();

        if ($doctors->isEmpty() || $patients->isEmpty() || ! $admin) {
            return;
        }

        $opd = Department::where('code', 'OPD')->first();
        $reasons = [
            'New consultation',
            'Follow-up review',
            'Lab result review',
            'Prescription refill',
            'Antenatal review',
            'Post-op review',
        ];
        $complaints = [
            'Fever and chills for 3 days',
            'Cough and sore throat',
            'Abdominal pain',
            'Headache and dizziness',
            'Routine BP check',
            'Diabetic review',
            null,
        ];

        $count = 0;
        foreach ($patients as $idx => $patient) {
            $doctor = $doctors->random();
            $dept = Department::find($doctor->department_id) ?? $opd;
            if (! $dept) {
                continue;
            }

            // Spread: past (-7..-1), today (0), future (+1..+10)
            $dayOffset = match (true) {
                $idx < 8  => -1 * rand(1, 7),
                $idx < 14 => 0,
                default   => rand(1, 10),
            };
            $date = Carbon::today()->addDays($dayOffset);
            $hour = rand(8, 16);
            $start = sprintf('%02d:%02d:00', $hour, Arr::random([0, 15, 30, 45]));
            $end = sprintf('%02d:%02d:00', $hour, Arr::random([15, 30, 45]) === 45 && $hour < 23 ? 45 : 30);

            $status = match (true) {
                $dayOffset < 0  => Arr::random(['completed', 'completed', 'completed', 'no_show']),
                $dayOffset === 0 => Arr::random(['confirmed', 'confirmed', 'scheduled', 'checked_in']),
                default          => Arr::random(['scheduled', 'scheduled', 'confirmed']),
            };

            Appointment::create([
                'appointment_number' => Appointment::generateAppointmentNumber(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'department_id' => $dept->id,
                'appointment_date' => $date->toDateString(),
                'start_time' => $start,
                'end_time' => $end,
                'visit_type' => 'outpatient',
                'priority' => 'normal',
                'chief_complaint' => Arr::random($complaints),
                'reason' => Arr::random($reasons),
                'consultation_mode' => 'in_person',
                'status' => $status,
                'created_by' => $admin->id,
            ]);

            $count++;
        }

        // Add one explicit cancellation example
        if ($count > 0 && $patients->count() > 0) {
            $p = $patients->first();
            $doc = $doctors->random();
            Appointment::create([
                'appointment_number' => Appointment::generateAppointmentNumber(),
                'patient_id' => $p->id,
                'doctor_id' => $doc->id,
                'department_id' => Department::find($doc->department_id)?->id ?? $opd?->id,
                'appointment_date' => Carbon::today()->addDays(2)->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '10:30:00',
                'visit_type' => 'outpatient',
                'priority' => 'normal',
                'reason' => 'Routine review',
                'consultation_mode' => 'in_person',
                'status' => 'cancelled',
                'cancelled_by' => $admin->id,
                'cancellation_reason' => 'Patient requested reschedule',
                'created_by' => $admin->id,
            ]);
        }
    }
}
