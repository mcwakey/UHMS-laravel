<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualAppointmentSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('appointments')) {
            return;
        }

        $existing = $this->countManual('appointments', 'appointment_number');
        $target = $this->target('appointments');
        if ($existing >= $target) {
            return;
        }

        $patients = $this->existingIds('patients', 'patient_number');
        $departments = $this->existingIds('departments', 'code');
        $users = DB::table('users')->where('email', 'like', '%@uhms.test')->pluck('id')->values();
        if (empty($patients) || empty($departments) || $users->isEmpty()) {
            return;
        }

        $statuses = ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'cancelled', 'no_show'];
        $rows = [];
        for ($i = $existing + 1; $i <= $target; $i++) {
            $date = today()->addDays(($i % 30) - 10);
            $status = $statuses[$i % count($statuses)];
            $rows[] = [
                'appointment_number' => $this->ref('APT', $i),
                'patient_id' => $patients[$i % count($patients)],
                'doctor_id' => $users[$i % $users->count()],
                'department_id' => $departments[$i % count($departments)],
                'appointment_date' => $date,
                'start_time' => sprintf('%02d:00:00', 8 + ($i % 8)),
                'end_time' => sprintf('%02d:30:00', 8 + ($i % 8)),
                'visit_type' => $i % 13 === 0 ? 'emergency' : 'outpatient',
                'priority' => $i % 11 === 0 ? 'urgent' : 'normal',
                'chief_complaint' => 'Manual appointment complaint '.$i,
                'reason' => 'Manual testing schedule scenario',
                'notes' => $this->metadata(['scenario' => $status]),
                'consultation_mode' => $i % 10 === 0 ? 'telehealth' : 'in_person',
                'status' => $status,
                'created_by' => $users[$i % $users->count()],
                'cancelled_by' => $status === 'cancelled' ? $users[$i % $users->count()] : null,
                'cancellation_reason' => $status === 'cancelled' ? 'Manual cancellation scenario' : null,
                'created_at' => $this->pastDate($i),
                'updated_at' => $this->now(),
            ];
        }

        $this->insert('appointments', $rows);
    }
}
