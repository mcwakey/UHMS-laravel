<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualVisitSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('visits')) {
            return;
        }

        $existing = $this->countManual('visits', 'visit_number');
        $target = $this->target('visits');
        if ($existing >= $target) {
            return;
        }

        $patients = $this->existingIds('patients', 'patient_number');
        $departments = $this->existingIds('departments', 'code');
        $users = DB::table('users')->where('email', 'like', '%@uhms.test')->pluck('id')->values();
        if (empty($patients) || empty($departments) || $users->isEmpty()) {
            return;
        }

        $statuses = ['created', 'registered', 'walked_in', 'checked_in', 'queued', 'triage', 'waiting', 'consulting', 'waiting_investigation', 'lab', 'pharmacy', 'billing', 'admitted', 'discharged', 'completed', 'cancelled', 'no_show', 'emergency'];
        $rows = [];
        for ($i = $existing + 1; $i <= $target; $i++) {
            $status = $statuses[$i % count($statuses)];
            $visitType = $status === 'emergency' || $i % 17 === 0 ? 'emergency' : ($i % 19 === 0 ? 'inpatient' : 'outpatient');
            $visitDate = $this->pastDate($i, 540);
            $rows[] = [
                'visit_number' => $this->ref('VIS', $i),
                'patient_id' => $patients[$i % count($patients)],
                'patient_age' => 18 + ($i % 70),
                'visit_type' => $visitType,
                'visit_source' => $i % 5 === 0 ? 'appointment' : 'direct',
                'attendance_class' => $i % 13 === 0 ? 'first_year' : ($i % 7 === 0 ? 'first_ever' : 'subsequent'),
                'visit_date' => $visitDate,
                'status' => $status,
                'priority' => $visitType === 'emergency' ? 'emergency' : ($i % 11 === 0 ? 'urgent' : 'normal'),
                'department_id' => $departments[$i % count($departments)],
                'current_department_id' => $departments[$i % count($departments)],
                'chief_complaint' => ['Fever and chills', 'Abdominal pain', 'Cough', 'Headache', 'Antenatal review'][$i % 5],
                'notes' => $this->metadata(['status_scenario' => $status]),
                'checked_in_at' => in_array($status, ['checked_in', 'queued', 'triage', 'waiting', 'consulting', 'completed', 'emergency'], true) ? $visitDate->copy()->setTime(8, $i % 60) : null,
                'checked_out_at' => in_array($status, ['completed', 'discharged'], true) ? $visitDate->copy()->setTime(12, $i % 60) : null,
                'arrived_at' => $visitDate->copy()->setTime(7, $i % 60),
                'created_by' => $users[$i % $users->count()],
                'created_at' => $visitDate,
                'updated_at' => $this->now(),
            ];
        }

        $this->insert('visits', $rows);
        $this->seedVisitLogsAndQueues();
    }

    private function seedVisitLogsAndQueues(): void
    {
        $visits = DB::table('visits')->where('visit_number', 'like', 'MT-VIS-%')->select('id', 'patient_id', 'status', 'current_department_id', 'created_by', 'created_at')->limit(2000)->get();
        $logRows = [];
        $queueRows = [];
        foreach ($visits as $visit) {
            $logRows[] = [
                'visit_id' => $visit->id,
                'from_status' => null,
                'to_status' => $visit->status,
                'changed_by' => $visit->created_by,
                'notes' => 'MT-MANUAL status transition seed',
                'timestamp' => $visit->created_at,
                'created_at' => $visit->created_at,
                'updated_at' => $this->now(),
            ];
            if (in_array($visit->status, ['queued', 'triage', 'waiting', 'consulting', 'emergency'], true)) {
                $queueRows[] = [
                    'visit_id' => $visit->id,
                    'patient_id' => $visit->patient_id,
                    'department_id' => $visit->current_department_id,
                    'queue_number' => 'MT-Q'.str_pad((string) $visit->id, 6, '0', STR_PAD_LEFT),
                    'status' => 'waiting',
                    'priority' => $visit->status === 'emergency' ? 'emergency' : 'normal',
                    'called_at' => null,
                    'served_at' => null,
                    'notes' => 'MT-MANUAL queue seed',
                    'created_at' => $visit->created_at,
                    'updated_at' => $this->now(),
                ];
            }
        }

        if ($this->countManual('visit_status_logs', 'notes') === 0) {
            $this->insert('visit_status_logs', $logRows);
        }
        if ($this->countManual('queue_entries', 'notes') === 0) {
            $this->insert('queue_entries', $queueRows);
        }
    }
}
