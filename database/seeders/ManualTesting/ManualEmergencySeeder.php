<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualEmergencySeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('emergency_cases')) {
            return;
        }

        $existing = $this->countManual('emergency_cases', 'emergency_number');
        $target = $this->target('emergency_cases');
        if ($existing >= $target) {
            return;
        }

        $visits = DB::table('visits')->where('visit_number', 'like', 'MT-VIS-%')->where('visit_type', 'emergency')->select('id', 'patient_id', 'created_by', 'created_at')->limit($target)->get()->values();
        if ($visits->isEmpty()) {
            return;
        }

        $deptId = DB::table('departments')->where('name', 'Emergency / Casualty')->value('id');
        $bayId = $this->ensureBay($deptId);
        $rows = [];
        $statuses = ['ARRIVED', 'TRIAGED', 'IN_TREATMENT', 'OBSERVATION', 'ADMITTED', 'DISCHARGED', 'TRANSFERRED', 'DECEASED'];
        foreach ($visits as $index => $visit) {
            $rows[] = [
                'emergency_number' => $this->ref('EMG', $existing + $index + 1),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'emergency_bay_id' => $bayId,
                'arrival_mode' => ['walk_in', 'ambulance', 'transfer'][$index % 3],
                'arrival_time' => $visit->created_at,
                'brought_by' => $index % 2 === 0 ? 'Relative' : 'Ambulance team',
                'source' => 'manual_test',
                'chief_complaint' => ['Road traffic accident', 'Severe abdominal pain', 'High fever', 'Breathing difficulty'][$index % 4],
                'initial_condition' => 'Manual emergency initial condition.',
                'triage_category' => ['RED', 'ORANGE', 'YELLOW', 'GREEN'][$index % 4],
                'triage_score' => 1 + ($index % 5),
                'triage_notes' => $this->metadata(['emergency' => true]),
                'emergency_status' => $statuses[$index % count($statuses)],
                'assigned_doctor_id' => $visit->created_by,
                'assigned_nurse_id' => $visit->created_by,
                'created_by' => $visit->created_by,
                'triaged_by' => $visit->created_by,
                'triaged_at' => now()->subHours($index % 48),
                'disposition' => ['discharged', 'admitted', 'transferred', 'death'][$index % 4],
                'disposition_notes' => 'Manual emergency disposition.',
                'disposition_time' => $index % 3 === 0 ? now()->subHours($index % 24) : null,
                'disposed_by' => $visit->created_by,
                'created_at' => $visit->created_at,
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('emergency_cases', $rows);
    }

    private function ensureBay(?int $departmentId): ?int
    {
        if (! $this->hasTable('emergency_bays')) {
            return null;
        }

        $code = $this->ref('BAY', 1, 3);
        $this->updateOrInsert('emergency_bays', ['code' => $code], [
            'name' => 'Manual Emergency Bay 1',
            'code' => $code,
            'bay_type' => 'TREATMENT',
            'department_id' => $departmentId,
            'status' => 'AVAILABLE',
            'notes' => 'Manual emergency bay.',
            'is_active' => true,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return DB::table('emergency_bays')->where('code', $code)->value('id');
    }
}
