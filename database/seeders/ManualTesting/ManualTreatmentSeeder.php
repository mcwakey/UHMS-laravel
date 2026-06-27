<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualTreatmentSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('admissions')) {
            return;
        }

        $existing = $this->countManual('admissions', 'admission_number');
        $target = $this->target('admissions');
        if ($existing >= $target) {
            return;
        }

        $bedIds = $this->anyIds('beds');
        $visits = DB::table('visits')->where('visit_number', 'like', 'MT-VIS-%')->select('id', 'patient_id', 'created_by', 'created_at')->limit($target)->get()->values();
        if (empty($bedIds) || $visits->isEmpty()) {
            return;
        }

        $rows = [];
        $statuses = ['admitted', 'discharged', 'transferred', 'deceased'];
        foreach ($visits as $index => $visit) {
            if ($index >= $target) {
                break;
            }
            $status = $statuses[$index % count($statuses)];
            $rows[] = [
                'admission_number' => $this->ref('ADM', $existing + $index + 1),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'bed_id' => $bedIds[$index % count($bedIds)],
                'admitted_by' => $visit->created_by,
                'admitting_diagnosis' => 'Manual admission diagnosis',
                'admission_date' => now()->subDays($index % 120),
                'expected_discharge_date' => today()->addDays(3 + ($index % 10)),
                'actual_discharge_date' => $status !== 'admitted' ? now()->subDays($index % 20) : null,
                'discharged_by' => $status !== 'admitted' ? $visit->created_by : null,
                'discharge_summary' => $status !== 'admitted' ? 'Manual discharge summary.' : null,
                'discharge_instructions' => $status !== 'admitted' ? 'Manual discharge instructions.' : null,
                'status' => $status,
                'created_at' => $visit->created_at,
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('admissions', $rows);
    }
}
